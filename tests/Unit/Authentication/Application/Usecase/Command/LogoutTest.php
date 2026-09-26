<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application\Usecase\Command;

use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;
use Src\Authentication\Application\Usecase\Command\Logout\Logout;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutInput;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutOutputPort;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final class LogoutTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_revokes_current_token_invalidates_session_and_clears_cookie(): void
    {
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('delete')->once()->with(Mockery::on(fn ($selector): bool => $selector->value() === 'selector'));
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('value')->once()->andReturn('selector.validator');
        $cookie->shouldReceive('clear')->once();
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('logout')->once();

        $output = (new Logout($tokens, $cookie, $auth))->execute(new LogoutInput);

        self::assertInstanceOf(LogoutOutputPort::class, $output);
    }
}
