<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginOutput;
use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutput;
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
        $this->mock(CompleteSocialLoginInterface::class, function ($mock) use ($identifier): void {
            $mock->shouldReceive('execute')->once()->andReturn(CompleteSocialLoginOutput::authenticated(
                $identifier,
                new GeneratePersistentLoginTokenOutput('selector', 'raw-validator', new \DateTimeImmutable('2099-01-31 00:00:00')),
            ));
        });

        $response = $this->get('/auth/social/google/callback?code=provider-code&state=state-value');
        $response
            ->assertRedirect('https://frontend.example/')
            ->assertCookie('persistent_login');
        $cookie = $response->getCookie('persistent_login', false);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(config('session.same_site'), $cookie->getSameSite());
        $location = $response->headers->get('Location');
        self::assertIsString($location);
        self::assertStringNotContainsString('selector', $location);
        self::assertStringNotContainsString('raw-validator', $location);

        self::assertSame($identifier->value(), session('account_identifier'));
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

        $response->assertRedirect('https://frontend.example/sign-up?social_registration=1')->assertCookieMissing('persistent_login');
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
            ->assertRedirect('https://frontend.example/login?social_login=failed')
            ->assertCookieMissing('persistent_login');

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
