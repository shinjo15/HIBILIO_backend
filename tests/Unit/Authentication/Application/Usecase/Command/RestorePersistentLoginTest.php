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
use Src\Authentication\Application\Usecase\Command\RestorePersistentLogin\RestorePersistentLogin;
use Src\Authentication\Application\Usecase\Command\RestorePersistentLogin\RestorePersistentLoginInput;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInterface;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateOutput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RestorePersistentLoginTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_valid_token_rotates_with_its_original_absolute_expiry_and_restores_session(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $expiresAt = new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-01 00:05:00'));
        $token = new PersistentLoginToken(new PersistentLoginSelector('old-selector'), $account, new PersistentLoginValidatorHash('old-hash'), $expiresAt);
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('find')->once()->with(Mockery::on(fn (PersistentLoginSelector $selector): bool => $selector->value() === 'old-selector'))->andReturn($token);
        $tokens->shouldReceive('delete')->once()->with(Mockery::on(fn (PersistentLoginSelector $selector): bool => $selector->value() === 'old-selector'));
        $tokens->shouldReceive('save')->once()->with(Mockery::on(function (PersistentLoginToken $rotated) use ($account, $expiresAt): bool {
            return $rotated->selector()->value() === 'new-selector'
                && $rotated->accountIdentifier()->value() === $account->value()
                && $rotated->validatorHash()->value() === 'new-hash'
                && $rotated->expiresAt() === $expiresAt;
        }));
        $clock = Mockery::mock(PersistentLoginClockServiceInterface::class);
        $clock->shouldReceive('now')->twice()->andReturn(new DateTimeImmutable('2099-01-01 00:00:00'));
        $hash = Mockery::mock(PersistentLoginTokenHashServiceInterface::class);
        $hash->shouldReceive('matches')->once()->with('old-validator', $token->validatorHash())->andReturnTrue();
        $hash->shouldReceive('hash')->once()->with('new-validator')->andReturn(new PersistentLoginValidatorHash('new-hash'));
        $generator = Mockery::mock(PersistentLoginTokenGeneratorServiceInterface::class);
        $generator->shouldReceive('selector')->once()->andReturn(new PersistentLoginSelector('new-selector'));
        $generator->shouldReceive('validator')->once()->andReturn('new-validator');
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('value')->once()->andReturn('old-selector.old-validator');
        $cookie->shouldReceive('queue')->once()->with('new-selector.new-validator', 5);
        $state = Mockery::mock(GetAuthenticatedAccountStateInterface::class);
        $state->shouldReceive('execute')->once()->andReturn(new GetAuthenticatedAccountStateOutput(true));
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('login')->once()->with($account);

        $output = (new RestorePersistentLogin($tokens, $generator, $hash, $clock, $cookie, $state, $auth))->execute(new RestorePersistentLoginInput);

        self::assertSame($account->value(), $output->accountIdentifier()?->value());
    }

    public function test_invalid_token_is_deleted_and_cookie_is_cleared(): void
    {
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('find')->once()->andReturnNull();
        $tokens->shouldReceive('delete')->once();
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('value')->andReturn('selector.validator');
        $cookie->shouldReceive('clear')->once();
        $generator = Mockery::mock(PersistentLoginTokenGeneratorServiceInterface::class);
        $hash = Mockery::mock(PersistentLoginTokenHashServiceInterface::class);
        $clock = Mockery::mock(PersistentLoginClockServiceInterface::class);
        $state = Mockery::mock(GetAuthenticatedAccountStateInterface::class);
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('login');

        self::assertNull((new RestorePersistentLogin($tokens, $generator, $hash, $clock, $cookie, $state, $auth))->execute(new RestorePersistentLoginInput)->accountIdentifier());
    }

    public function test_expired_token_is_deleted_without_restoring_session(): void
    {
        $this->assertRejectedToken(new DateTimeImmutable('2099-01-01 00:00:00'), true, true);
    }

    public function test_validator_mismatch_deletes_token_and_does_not_restore_session(): void
    {
        $this->assertRejectedToken(new DateTimeImmutable('2099-01-02 00:00:00'), false, true);
    }

    public function test_inactive_account_deletes_token_and_does_not_restore_session(): void
    {
        $this->assertRejectedToken(new DateTimeImmutable('2099-01-02 00:00:00'), true, false);
    }

    private function assertRejectedToken(DateTimeImmutable $expiresAt, bool $hashMatches, bool $authenticated): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $token = new PersistentLoginToken(new PersistentLoginSelector('selector'), $account, new PersistentLoginValidatorHash('hash'), new PersistentLoginExpiresAt($expiresAt));
        $tokens = Mockery::mock(PersistentLoginTokenRepositoryInterface::class);
        $tokens->shouldReceive('find')->once()->andReturn($token);
        $tokens->shouldReceive('delete')->once();
        $cookie = Mockery::mock(PersistentLoginCookieServiceInterface::class);
        $cookie->shouldReceive('value')->once()->andReturn('selector.validator');
        $cookie->shouldReceive('clear')->once();
        $clock = Mockery::mock(PersistentLoginClockServiceInterface::class);
        $clock->shouldReceive('now')->once()->andReturn(new DateTimeImmutable('2099-01-01 00:00:00'));
        $hash = Mockery::mock(PersistentLoginTokenHashServiceInterface::class);
        if ($expiresAt > new DateTimeImmutable('2099-01-01 00:00:00')) {
            $hash->shouldReceive('matches')->once()->andReturn($hashMatches);
        }
        $state = Mockery::mock(GetAuthenticatedAccountStateInterface::class);
        if ($hashMatches && $expiresAt > new DateTimeImmutable('2099-01-01 00:00:00')) {
            $state->shouldReceive('execute')->once()->andReturn(new GetAuthenticatedAccountStateOutput($authenticated));
        }
        $generator = Mockery::mock(PersistentLoginTokenGeneratorServiceInterface::class);
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('login');

        self::assertNull((new RestorePersistentLogin($tokens, $generator, $hash, $clock, $cookie, $state, $auth))->execute(new RestorePersistentLoginInput)->accountIdentifier());
    }
}
