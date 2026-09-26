<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginToken;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginTokenInput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Application\Service\HashServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AuthenticateWithPersistentLoginTokenTest extends TestCase
{
    public function test_authenticates_an_active_account_with_a_matching_unexpired_token(): void
    {
        $account = $this->account();
        $output = $this->authenticate($this->token($account->accountIdentifier()), $account, true)->execute(
            new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'),
        );

        self::assertSame($account->accountIdentifier(), $output->accountIdentifier());
    }

    public function test_rejects_a_missing_expired_or_mismatched_token(): void
    {
        $account = $this->account();
        $expired = $this->token($account->accountIdentifier(), new DateTimeImmutable('2000-01-01 00:00:00'));
        $exactBoundary = $this->token($account->accountIdentifier(), new DateTimeImmutable);

        self::assertNull($this->authenticate(null, $account, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
        self::assertNull($this->authenticate($expired, $account, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
        self::assertNull($this->authenticate($exactBoundary, $account, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
        self::assertNull($this->authenticate($this->token($account->accountIdentifier()), $account, false)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
    }

    public function test_rejects_a_missing_or_banned_account(): void
    {
        $account = $this->account();
        $token = $this->token($account->accountIdentifier());
        $temporarilyBanned = $this->account();
        $temporarilyBanned->temporarilyBan();
        $permanentlyBanned = $this->account();
        $permanentlyBanned->permanentlyBan();

        self::assertNull($this->authenticate($token, null, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
        self::assertNull($this->authenticate($token, $temporarilyBanned, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
        self::assertNull($this->authenticate($token, $permanentlyBanned, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'))->accountIdentifier());
    }

    private function authenticate(?PersistentLoginToken $token, ?Account $account, bool $matches): AuthenticateWithPersistentLoginToken
    {
        return new AuthenticateWithPersistentLoginToken(new InMemoryPersistentLoginTokens($token), new FixedHashService($matches), new InMemoryPersistentLoginAccounts($account));
    }

    private function token(AccountIdentifier $accountIdentifier, ?DateTimeImmutable $expiresAt = null): PersistentLoginToken
    {
        return new PersistentLoginToken(new PersistentLoginSelector('selector'), $accountIdentifier, new PersistentLoginValidatorHash('hash'), new PersistentLoginExpiresAt($expiresAt ?? new DateTimeImmutable('2099-01-31 00:00:00')));
    }

    private function account(): Account
    {
        return Account::create(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));
    }
}

final class InMemoryPersistentLoginTokens implements PersistentLoginTokenRepositoryInterface
{
    public function __construct(private ?PersistentLoginToken $token) {}

    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        return $this->token;
    }

    public function save(PersistentLoginToken $token): void {}

    public function deleteBySelector(PersistentLoginSelector $selector): void {}
}
final class FixedHashService implements HashServiceInterface
{
    public function __construct(private bool $matches) {}

    public function hash(string $value): string
    {
        return 'hash';
    }

    public function matches(string $value, string $hash): bool
    {
        return $this->matches;
    }
}
final class InMemoryPersistentLoginAccounts implements AccountRepositoryInterface
{
    public function __construct(private ?Account $account) {}

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        return $this->account;
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return null;
    }

    public function save(Account $account): void {}

    public function updateProfile(Account $account): void {}
}
