<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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

        self::assertNull((new LaravelAuthService($request))->accountIdentifier());
    }

    public function test_login_regenerates_session_and_stores_account_identifier(): void
    {
        $accountIdentifier = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $this->insertAccount($accountIdentifier, true, 'active');
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        $authService = new LaravelAuthService($request);

        $authService->login(new AccountIdentifier($accountIdentifier));

        self::assertSame($accountIdentifier, $authService->accountIdentifier());
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

        self::assertNull((new LaravelAuthService($request))->accountIdentifier());
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
}
