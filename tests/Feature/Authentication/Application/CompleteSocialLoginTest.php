<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Application;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLogin;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInput;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Tests\TestCase;

final class CompleteSocialLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_account_and_connection_for_a_new_verified_email(): void
    {
        $this->fakeProfile('new-user', 'new@example.com', 'New User');
        $output = $this->complete();

        self::assertTrue($output->isAuthenticated());
        self::assertSame(1, DB::table('accounts')->count());
        self::assertDatabaseHas('accounts', ['email_address' => 'new@example.com', 'account_name' => 'New User']);
        self::assertDatabaseHas('social_login_connections', ['provider' => 'google', 'provider_user_identifier' => 'new-user']);
    }

    public function test_links_a_new_provider_connection_to_an_existing_verified_email(): void
    {
        $accountIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($accountIdentifier, 'existing@example.com', 'Existing');
        $this->fakeProfile('existing-google-id', 'existing@example.com', null);
        $output = $this->complete();

        self::assertSame($accountIdentifier, $output->accountIdentifier()?->value());
        self::assertSame(1, DB::table('accounts')->count());
        self::assertDatabaseHas('social_login_connections', ['account_identifier' => $accountIdentifier, 'provider_user_identifier' => 'existing-google-id']);
    }

    public function test_uses_the_existing_provider_connection_without_creating_a_duplicate(): void
    {
        $accountIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->insertAccount($accountIdentifier, 'connected@example.com', 'Connected');
        DB::table('social_login_connections')->insert([
            'account_identifier' => $accountIdentifier,
            'provider' => 'apple',
            'provider_user_identifier' => 'apple-sub',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->fakeProfile('apple-sub', 'different@example.com', null);
        $output = $this->complete('apple');

        self::assertSame($accountIdentifier, $output->accountIdentifier()?->value());
        self::assertSame(1, DB::table('accounts')->count());
    }

    public function test_rejects_a_new_provider_identity_without_account_name(): void
    {
        $this->fakeProfile('no-name', 'no-name@example.com', null);
        $output = $this->complete();

        self::assertFalse($output->isAuthenticated());
        self::assertSame(0, DB::table('accounts')->count());
        self::assertSame(0, DB::table('social_login_connections')->count());
    }

    private function complete(string $provider = 'google')
    {
        return $this->app->make(CompleteSocialLogin::class)->execute(new CompleteSocialLoginInput(
            SocialLoginProvider::from($provider), 'authorization-code', 'state', 'browser-session',
        ));
    }

    private function fakeProfile(string $providerId, string $email, ?string $accountName): void
    {
        $this->mock(SocialLoginServiceInterface::class, function ($mock) use ($providerId, $email, $accountName): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([
                'provider_user_identifier' => $providerId,
                'email_address' => $email,
                'account_name' => $accountName,
            ]);
        });
    }

    private function insertAccount(string $identifier, string $email, string $name): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier, 'account_name' => $name, 'email_address' => $email,
            'available' => true, 'status' => 'active', 'visibility' => 'public', 'ui_mode' => 'system',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
