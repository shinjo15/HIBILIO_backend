<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginToken;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginTokenInput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Factory\GeneratedPersistentLoginToken;
use Src\Authentication\Domain\Factory\PersistentLoginTokenFactoryInterface;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Application\Service\HashServiceInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
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

        $missing = $this->authenticate(null, $account, true)->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'));
        self::assertNull($missing->accountIdentifier());
        self::assertNull($missing->persistentLoginToken());
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

    public function test_rotates_a_valid_token_and_invalidates_its_old_selector(): void
    {
        $account = $this->account();
        $token = $this->token($account->accountIdentifier());
        $tokens = new InMemoryPersistentLoginTokens($token);
        $output = (new AuthenticateWithPersistentLoginToken($tokens, new FixedHashService(true), new InMemoryPersistentLoginAccounts($account), new RotatingPersistentLoginTokenFactory, new ImmediateTransactionManager))->execute(new AuthenticateWithPersistentLoginTokenInput($token->selector(), 'raw-validator'));

        self::assertSame($account->accountIdentifier(), $output->accountIdentifier());
        self::assertNotNull($output->persistentLoginToken());
        self::assertSame('new-selector', $output->persistentLoginToken()->selector());
        self::assertSame('new-validator', $output->persistentLoginToken()->rawValidator());
        self::assertNull($tokens->findBySelector($token->selector()));
        self::assertSame($token->expiresAt(), $tokens->savedToken?->expiresAt());
    }

    public function test_revokes_expired_mismatched_missing_banned_and_unavailable_accounts(): void
    {
        $account = $this->account();
        $unavailable = Account::restore($account->accountIdentifier(), $account->accountName(), null, $account->emailAddress(), [], new FavoriteTagIdentifiers([]), AccountStatus::ACTIVE, null, available: false);
        $banned = $this->account();
        $banned->permanentlyBan();

        foreach ([
            [$this->token($account->accountIdentifier(), new DateTimeImmutable('2000-01-01')), $account, true],
            [$this->token($account->accountIdentifier()), $account, false],
            [$this->token($account->accountIdentifier()), null, true],
            [$this->token($account->accountIdentifier()), $banned, true],
            [$this->token($account->accountIdentifier()), $unavailable, true],
        ] as [$token, $candidate, $matches]) {
            $tokens = new InMemoryPersistentLoginTokens($token);
            $output = (new AuthenticateWithPersistentLoginToken($tokens, new FixedHashService($matches), new InMemoryPersistentLoginAccounts($candidate), new RotatingPersistentLoginTokenFactory, new ImmediateTransactionManager))->execute(new AuthenticateWithPersistentLoginTokenInput($token->selector(), 'raw-validator'));

            self::assertNull($output->accountIdentifier());
            self::assertNull($output->persistentLoginToken());
            self::assertSame(1, $tokens->deleteCount);
        }
    }

    public function test_rejects_a_consume_race_without_saving_a_rotated_token(): void
    {
        $account = $this->account();
        $tokens = new InMemoryPersistentLoginTokens($this->token($account->accountIdentifier()), false);
        $output = (new AuthenticateWithPersistentLoginToken($tokens, new FixedHashService(true), new InMemoryPersistentLoginAccounts($account), new RotatingPersistentLoginTokenFactory, new ImmediateTransactionManager))->execute(new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw-validator'));

        self::assertNull($output->accountIdentifier());
        self::assertNull($output->persistentLoginToken());
        self::assertNull($tokens->savedToken);
    }

    private function authenticate(?PersistentLoginToken $token, ?Account $account, bool $matches): AuthenticateWithPersistentLoginToken
    {
        return new AuthenticateWithPersistentLoginToken(new InMemoryPersistentLoginTokens($token), new FixedHashService($matches), new InMemoryPersistentLoginAccounts($account), new RotatingPersistentLoginTokenFactory, new ImmediateTransactionManager);
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
    public int $deleteCount = 0;

    public ?PersistentLoginToken $savedToken = null;

    public function __construct(private ?PersistentLoginToken $token, private bool $canDelete = true) {}

    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        return $this->token;
    }

    public function save(PersistentLoginToken $token): void
    {
        $this->savedToken = $token;
    }

    public function deleteBySelector(PersistentLoginSelector $selector): bool
    {
        $this->deleteCount++;
        if (! $this->canDelete || $this->token === null) {
            return false;
        }

        $this->token = null;

        return true;
    }
}
final class RotatingPersistentLoginTokenFactory implements PersistentLoginTokenFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, DateTimeImmutable $issuedAt): GeneratedPersistentLoginToken
    {
        return $this->rotate(new PersistentLoginToken(new PersistentLoginSelector('selector'), $accountIdentifier, new PersistentLoginValidatorHash('hash'), new PersistentLoginExpiresAt($issuedAt)));
    }

    public function rotate(PersistentLoginToken $existing): GeneratedPersistentLoginToken
    {
        return new GeneratedPersistentLoginToken(new PersistentLoginToken(new PersistentLoginSelector('new-selector'), $existing->accountIdentifier(), new PersistentLoginValidatorHash('new-hash'), $existing->expiresAt()), 'new-validator');
    }
}
final class ImmediateTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
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
