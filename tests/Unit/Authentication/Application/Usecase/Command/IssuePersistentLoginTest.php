<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application\Usecase\Command;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Src\Authentication\Application\Service\PersistentLoginClockServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenGeneratorServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenHashServiceInterface;
use Src\Authentication\Application\Usecase\Command\IssuePersistentLogin\IssuePersistentLogin;
use Src\Authentication\Application\Usecase\Command\IssuePersistentLogin\IssuePersistentLoginInput;
use Src\Authentication\Application\Usecase\Command\IssuePersistentLogin\IssuePersistentLoginOutput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class IssuePersistentLoginTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_issues_a_hashed_token_with_a_fixed_thirty_day_deadline_establishes_session_and_queues_cookie(): void
    {
        $accountIdentifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('save')->once()->with(Mockery::on(function (PersistentLoginToken $token) use ($accountIdentifier): bool {
            return $token->accountIdentifier()->value() === $accountIdentifier->value()
                && $token->selector()->value() === 'selector'
                && $token->validatorHash()->value() === 'hash'
                && $token->expiresAt()->value()->format('Y-m-d H:i:s') === '2099-01-31 00:00:00';
        }));
        $generator = Mockery::mock(PersistentLoginTokenGeneratorServiceInterface::class);
        $generator->shouldReceive('selector')->once()->andReturn(new PersistentLoginSelector('selector'));
        $generator->shouldReceive('validator')->once()->andReturn('validator');
        $hashService = Mockery::mock(PersistentLoginTokenHashServiceInterface::class);
        $hashService->shouldReceive('hash')->once()->with('validator')->andReturn(new PersistentLoginValidatorHash('hash'));
        $clock = Mockery::mock(PersistentLoginClockServiceInterface::class);
        $clock->shouldReceive('now')->once()->andReturn(new DateTimeImmutable('2099-01-01 00:00:00'));
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('queue')->once()->with('selector.validator', 43200);
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('login')->once()->with($accountIdentifier);

        $output = (new IssuePersistentLogin($tokens, $generator, $hashService, $clock, $cookie, $authService))
            ->execute(new IssuePersistentLoginInput($accountIdentifier));

        self::assertInstanceOf(IssuePersistentLoginOutput::class, $output);
    }
}
