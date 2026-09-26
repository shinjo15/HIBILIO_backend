<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Application;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLogin;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInput;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Tests\TestCase;

final class CompleteSocialLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_verified_identity_does_not_create_an_account_and_returns_pending_registration(): void
    {
        $this->mock(SocialLoginServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([
                'provider_user_identifier' => 'google-user',
                'email_address' => 'verified@example.com',
            ]);
        });

        $output = $this->app->make(CompleteSocialLogin::class)->execute(new CompleteSocialLoginInput(
            SocialLoginProvider::GOOGLE,
            'authorization-code',
            'state-token',
            'browser-session',
        ));

        self::assertFalse($output->isAuthenticated());
        self::assertNotNull($output->pendingRegistration());
        self::assertSame('google', $output->pendingRegistration()?->provider()->value);
        self::assertSame('google-user', $output->pendingRegistration()?->providerUserIdentifier());
        self::assertSame('verified@example.com', $output->pendingRegistration()?->emailAddress()->value());
        self::assertSame(0, DB::table('accounts')->count());
        self::assertSame(0, DB::table('social_login_connections')->count());
        self::assertSame(0, DB::table('persistent_login_tokens')->count());
    }

    public function test_existing_provider_connection_logs_into_its_account_without_using_the_email(): void
    {
        $accountIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($accountIdentifier, 'linked@example.com', 'Linked');
        DB::table('social_login_connections')->insert([
            'account_identifier' => $accountIdentifier,
            'provider' => 'google',
            'provider_user_identifier' => 'google-user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->fakeProfile('google-user', 'different@example.com');
        Cookie::flushQueuedCookies();

        $output = $this->complete();

        self::assertTrue($output->isAuthenticated());
        self::assertSame($accountIdentifier, $output->accountIdentifier()?->value());
        self::assertSame(1, DB::table('accounts')->count());
        self::assertSame($accountIdentifier, $this->app['session.store']->get('account_identifier'));
        $cookies = Cookie::getQueuedCookies();
        self::assertCount(1, $cookies);
        self::assertTrue($cookies[0]->isSecure());
        self::assertTrue($cookies[0]->isHttpOnly());
        self::assertSame('lax', $cookies[0]->getSameSite());
        [$selector, $validator] = explode('.', $cookies[0]->getValue(), 2);
        $token = DB::table('persistent_login_tokens')->where('selector', $selector)->first();
        self::assertNotNull($token);
        self::assertNotSame($validator, $token->validator_hash);
        self::assertTrue(Hash::check($validator, $token->validator_hash));
    }

    public function test_existing_verified_email_is_linked_without_creating_a_duplicate_account(): void
    {
        $accountIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->insertAccount($accountIdentifier, 'existing@example.com', 'Existing');
        $this->fakeProfile('google-user', 'existing@example.com');

        $output = $this->complete();

        self::assertTrue($output->isAuthenticated());
        self::assertSame($accountIdentifier, $output->accountIdentifier()?->value());
        self::assertSame(1, DB::table('accounts')->count());
        $this->assertDatabaseHas('social_login_connections', [
            'account_identifier' => $accountIdentifier,
            'provider' => 'google',
            'provider_user_identifier' => 'google-user',
        ]);
    }

    private function complete(): mixed
    {
        $this->app['request']->setLaravelSession($this->app['session.store']);

        return $this->app->make(CompleteSocialLogin::class)->execute(new CompleteSocialLoginInput(
            SocialLoginProvider::GOOGLE,
            'authorization-code',
            'state-token',
            'browser-session',
        ));
    }

    private function fakeProfile(string $providerUserIdentifier, string $emailAddress): void
    {
        $this->mock(SocialLoginServiceInterface::class, function ($mock) use ($providerUserIdentifier, $emailAddress): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([
                'provider_user_identifier' => $providerUserIdentifier,
                'email_address' => $emailAddress,
            ]);
        });
    }

    private function insertAccount(string $identifier, string $emailAddress, string $accountName): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $accountName,
            'email_address' => $emailAddress,
            'available' => true,
            'status' => 'active',
            'visibility' => 'public',
            'ui_mode' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
