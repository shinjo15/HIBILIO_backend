<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginOutput;
use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CompleteSocialLoginActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.frontend_url' => 'https://frontend.example']);
    }

    public function test_existing_account_callback_redirects_to_frontend_home_and_logs_in(): void
    {
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        DB::table('accounts')->insert([
            'account_identifier' => $identifier->value(),
            'account_name' => 'ソーシャルログインAccount',
            'email_address' => 'social-login@example.com',
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->mock(CompleteSocialLoginInterface::class, function ($mock) use ($identifier): void {
            $mock->shouldReceive('execute')->once()->andReturn(CompleteSocialLoginOutput::authenticated($identifier));
        });

        $this->get('/auth/social/google/callback?code=provider-code&state=state-value')
            ->assertRedirect('https://frontend.example/');
    }

    public function test_unknown_account_callback_stores_pending_registration_and_redirects_without_identity_data(): void
    {
        $this->mock(CompleteSocialLoginInterface::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andReturn(CompleteSocialLoginOutput::pending(
                new PendingSocialRegistration(
                    SocialLoginProvider::GOOGLE,
                    'provider-user',
                    'verified@example.com',
                ),
            ));
        });

        $response = $this->get('/auth/social/google/callback?code=provider-code&state=state-value');

        $response->assertRedirect('https://frontend.example/sign-up?social_registration=1');
        $location = $response->headers->get('Location');
        self::assertIsString($location);
        self::assertStringNotContainsString('provider-user', $location);
        self::assertStringNotContainsString('verified@example.com', $location);
        self::assertSame('provider-user', session('pending_social_registration.provider_user_identifier'));
    }

    public function test_failed_callback_redirects_to_frontend_login(): void
    {
        $this->mock(CompleteSocialLoginInterface::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andReturn(CompleteSocialLoginOutput::rejected());
        });

        $this->get('/auth/social/apple/callback?code=bad&state=state-value')
            ->assertRedirect('https://frontend.example/login?social_login=failed');

        self::assertNull(session('account_identifier'));
    }

    public function test_denial_consumes_state_and_redirects_to_frontend_login(): void
    {
        $this->mock(CompleteSocialLoginInterface::class, function ($mock): void {
            $mock->shouldNotReceive('execute');
        });
        $this->mock(SocialLoginServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('discard')->once();
        });

        $this->get('/auth/social/google/callback?error=access_denied&state=state-value')
            ->assertRedirect('https://frontend.example/login?social_login=failed');
    }
}
