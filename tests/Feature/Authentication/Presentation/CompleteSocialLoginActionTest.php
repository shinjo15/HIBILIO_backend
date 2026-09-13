<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginOutput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CompleteSocialLoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_in_after_a_successful_google_callback(): void
    {
        $identifier = '11111111-1111-4111-8111-111111111111';
        $this->mock(CompleteSocialLoginInterface::class, function ($mock) use ($identifier): void {
            $mock->shouldReceive('execute')->once()->andReturn(
                CompleteSocialLoginOutput::authenticated(new AccountIdentifier($identifier)),
            );
        });

        $this->get('/auth/social/google/callback?code=provider-code&state=state-value')
            ->assertNoContent();

        self::assertSame($identifier, session('account_identifier'));
    }

    public function test_does_not_log_in_when_provider_callback_is_rejected(): void
    {
        $this->mock(CompleteSocialLoginInterface::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andReturn(CompleteSocialLoginOutput::rejected());
        });

        $this->get('/auth/social/apple/callback?code=bad&state=state-value')
            ->assertUnauthorized();

        self::assertNull(session('account_identifier'));
    }

    public function test_denial_is_consumed_without_attempting_provider_authentication(): void
    {
        $this->mock(CompleteSocialLoginInterface::class, function ($mock): void {
            $mock->shouldNotReceive('execute');
        });
        $this->mock(SocialLoginServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('discard')->once();
        });

        $this->get('/auth/social/google/callback?error=access_denied&state=state-value')
            ->assertUnauthorized();
    }
}
