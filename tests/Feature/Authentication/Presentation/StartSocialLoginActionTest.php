<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Mockery;
use Src\Authentication\Application\Usecase\Command\StartSocialLogin\StartSocialLoginInputPort;
use Src\Authentication\Application\Usecase\Command\StartSocialLogin\StartSocialLoginInterface;
use Src\Authentication\Application\Usecase\Command\StartSocialLogin\StartSocialLoginOutput;
use Tests\TestCase;

final class StartSocialLoginActionTest extends TestCase
{
    public function test_starts_social_login_with_the_current_browser_session(): void
    {
        $this->mock(StartSocialLoginInterface::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(function (StartSocialLoginInputPort $input): bool {
                    return $input->provider()->value === 'google'
                        && $input->browserSessionIdentifier() !== '';
                }))
                ->andReturn(new StartSocialLoginOutput('https://accounts.google.com/test'));
        });

        $this->get('/auth/social/google')->assertRedirect('https://accounts.google.com/test');
    }

    public function test_accepts_apple_provider(): void
    {
        $this->mock(StartSocialLoginInterface::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andReturn(
                new StartSocialLoginOutput('https://appleid.apple.com/test'),
            );
        });

        $this->get('/auth/social/apple')->assertRedirect('https://appleid.apple.com/test');
    }
}
