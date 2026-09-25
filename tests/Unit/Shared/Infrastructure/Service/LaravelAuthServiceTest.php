<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Service;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class LaravelAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_identifier_returns_null_when_session_has_no_account_identifier(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);

        self::assertNull($this->authService($request)->accountIdentifier());
    }

    public function test_login_regenerates_session_and_stores_account_identifier(): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $this->insertAccount($accountIdentifier, true, 'active');
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);

        $this->authService($request)->login(new AccountIdentifier($accountIdentifier));

        self::assertSame($accountIdentifier, $this->authService($request)->accountIdentifier());
    }

    public function test_login_creates_a_hashed_persistent_login_token_and_queues_a_secure_cookie(): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $this->insertAccount($accountIdentifier, true, 'active');
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);

        $this->authService($request)->login(new AccountIdentifier($accountIdentifier));

        $token = DB::table('persistent_login_tokens')->sole();
        self::assertSame($accountIdentifier, $token->account_identifier);
        self::assertSame(64, strlen($token->selector));
        self::assertNotSame($token->selector, $token->validator_hash);
        self::assertGreaterThan(now()->addDays(29), CarbonImmutable::parse($token->expires_at));
        self::assertLessThan(now()->addDays(31), CarbonImmutable::parse($token->expires_at));

        $cookie = $this->queuedPersistentLoginCookie();
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('lax', strtolower((string) $cookie->getSameSite()));
        self::assertSame('/', $cookie->getPath());
        self::assertSame(60 * 60 * 24 * 30, $cookie->getMaxAge());
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $cookie->getValue());
        self::assertFalse(Hash::check($cookie->getValue(), $token->validator_hash));
    }

    public function test_account_identifier_restores_session_and_rotates_a_valid_persistent_login_token(): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $selector = 'selector-value';
        $validator = 'validator-value';
        $expiresAt = new \DateTimeImmutable('2099-01-15 12:34:56');
        $this->insertAccount($accountIdentifier, true, 'active');
        DB::table('persistent_login_tokens')->insert([
            'selector' => $selector,
            'account_identifier' => $accountIdentifier,
            'validator_hash' => Hash::make($validator),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/', 'GET', [], [LaravelAuthService::PERSISTENT_LOGIN_COOKIE => $selector.'.'.$validator]);
        $request->setLaravelSession($this->app['session.store']);

        self::assertSame($accountIdentifier, $this->authService($request)->accountIdentifier());
        self::assertSame($accountIdentifier, $request->session()->get(LaravelAuthService::SESSION_KEY));
        self::assertSame(1, DB::table('persistent_login_tokens')->count());
        self::assertFalse(DB::table('persistent_login_tokens')->where('selector', $selector)->exists());
        self::assertSame($expiresAt->format('Y-m-d H:i:s'), CarbonImmutable::parse(
            DB::table('persistent_login_tokens')->sole()->expires_at,
        )->format('Y-m-d H:i:s'));
    }

    #[DataProvider('invalidPersistentLoginTokens')]
    public function test_account_identifier_deletes_invalid_persistent_login_tokens_and_clears_cookie(
        bool $validatorMatches,
        \DateTimeInterface $expiresAt,
        bool $available,
        string $status,
    ): void {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $selector = 'selector-value';
        $this->insertAccount($accountIdentifier, $available, $status);
        DB::table('persistent_login_tokens')->insert([
            'selector' => $selector,
            'account_identifier' => $accountIdentifier,
            'validator_hash' => Hash::make($validatorMatches ? 'validator-value' : 'another-validator'),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/', 'GET', [], [LaravelAuthService::PERSISTENT_LOGIN_COOKIE => $selector.'.validator-value']);
        $request->setLaravelSession($this->app['session.store']);

        self::assertNull($this->authService($request)->accountIdentifier());
        self::assertFalse(DB::table('persistent_login_tokens')->where('selector', $selector)->exists());
        self::assertSame(0, $this->queuedPersistentLoginCookie()?->getMaxAge());
    }

    /** @return array<string, array{bool, \DateTimeInterface, bool, string}> */
    public static function invalidPersistentLoginTokens(): array
    {
        return [
            'invalid validator' => [false, new \DateTimeImmutable('+1 day'), true, 'active'],
            'expired' => [true, new \DateTimeImmutable('-1 second'), true, 'active'],
            'inactive account' => [true, new \DateTimeImmutable('+1 day'), false, 'active'],
        ];
    }

    public function test_logout_deletes_the_current_persistent_login_token_invalidates_session_and_clears_cookie(): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $this->insertAccount($accountIdentifier, true, 'active');
        DB::table('persistent_login_tokens')->insert([
            'selector' => 'selector-value',
            'account_identifier' => $accountIdentifier,
            'validator_hash' => Hash::make('validator-value'),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/', 'POST', [], [LaravelAuthService::PERSISTENT_LOGIN_COOKIE => 'selector-value.validator-value']);
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put(LaravelAuthService::SESSION_KEY, $accountIdentifier);
        $csrfToken = $request->session()->token();

        $this->authService($request)->logout();

        self::assertFalse($request->session()->has(LaravelAuthService::SESSION_KEY));
        self::assertNotSame($csrfToken, $request->session()->token());
        self::assertFalse(DB::table('persistent_login_tokens')->where('selector', 'selector-value')->exists());
        self::assertSame(0, $this->queuedPersistentLoginCookie()?->getMaxAge());
    }

    #[DataProvider('unauthenticatedAccountStates')]
    public function test_account_identifier_forgets_session_when_account_is_not_active(?bool $available, ?string $status): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        if ($available !== null && $status !== null) {
            $this->insertAccount($accountIdentifier, $available, $status);
        }

        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->put(LaravelAuthService::SESSION_KEY, $accountIdentifier);

        self::assertNull($this->authService($request)->accountIdentifier());
        self::assertFalse($request->session()->has(LaravelAuthService::SESSION_KEY));
    }

    /** @return array<string, array{?bool, ?string}> */
    public static function unauthenticatedAccountStates(): array
    {
        return [
            'unavailable' => [false, 'active'],
            'temporarily banned' => [true, 'temporarily_banned'],
            'permanently banned' => [true, 'permanently_banned'],
            'deleted' => [null, null],
        ];
    }

    private function insertAccount(string $accountIdentifier, bool $available, string $status): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => 'テストAccount',
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authService(Request $request): LaravelAuthService
    {
        return new LaravelAuthService($request, $this->app->make(PersistentLoginTokenRepositoryInterface::class));
    }

    private function queuedPersistentLoginCookie(): mixed
    {
        return collect($this->app['cookie']->getQueuedCookies())
            ->first(fn ($cookie): bool => $cookie->getName() === LaravelAuthService::PERSISTENT_LOGIN_COOKIE);
    }
}
