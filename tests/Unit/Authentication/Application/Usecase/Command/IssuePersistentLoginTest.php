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

    public function test_issues_a_hashed_token_with_a_fixed_thirty_day_deadline_and_establishes_session(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $now = new DateTimeImmutable('2099-01-01 00:00:00');
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('save')->once()->with(Mockery::on(function (PersistentLoginToken $token) use ($account): bool {
            return $token->accountIdentifier()->value() === $account->value()
                && $token->selector()->value() === 'selector'
                && $token->validatorHash()->value() === 'hash'
                && $token->expiresAt()->value()->format('Y-m-d H:i:s') === '2099-01-31 00:00:00';
        }));
        $generator = Mockery::mock(PersistentLoginTokenGeneratorServiceInterface::class);
        $generator->shouldReceive('selector')->andReturn(new PersistentLoginSelector('selector'));
        $generator->shouldReceive('validator')->andReturn('validator');
        $hash = Mockery::mock(PersistentLoginTokenHashServiceInterface::class);
        $hash->shouldReceive('hash')->with('validator')->andReturn(new PersistentLoginValidatorHash('hash'));
        $clock = Mockery::mock(PersistentLoginClockServiceInterface::class);
        $clock->shouldReceive('now')->andReturn($now);
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('queue')->once()->with('selector.validator', 43200);
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('login')->once()->with($account);

        (new IssuePersistentLogin($tokens, $generator, $hash, $clock, $cookie, $auth))->execute(new IssuePersistentLoginInput($account));

        self::assertTrue(true);
    }
}
