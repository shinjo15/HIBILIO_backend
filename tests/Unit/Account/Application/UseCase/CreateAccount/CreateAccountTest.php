<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Application\UseCase\CreateAccount;

use PHPUnit\Framework\TestCase;
use Src\Account\Application\Service\AccountRegistrationMailServiceInterface;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Application\UseCase\CreateAccount\CreateAccount;
use Src\Account\Application\UseCase\CreateAccount\CreateAccountInput;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Exception\DuplicateEmailAddressException;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateAccountTest extends TestCase
{
    public function test_saves_an_account_then_sends_a_registration_email(): void
    {
        $account = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountName('朝活ユーザー'),
            new AccountBio('朝の時間を大切にしています。'),
            new EmailAddress('user@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
        );
        $accountRepository = new InMemoryAccountRepository;
        $mailService = new FakeAccountRegistrationMailService;

        (new CreateAccount(
            accountRepository: $accountRepository,
            accountFactory: new FakeAccountFactory($account),
            accountRegistrationMailService: $mailService,
            transactionManager: new ImmediateTransactionManager,
            storageService: new FakeStorageService,
        ))->execute(new CreateAccountInput(
            accountName: new AccountName('朝活ユーザー'),
            accountBio: new AccountBio('朝の時間を大切にしています。'),
            emailAddress: new EmailAddress('user@example.com'),
            socialLinks: [],
            favoriteTagIdentifiers: new FavoriteTagIdentifiers([]),
        ));

        self::assertSame($account, $accountRepository->savedAccount);
        self::assertSame(['user@example.com', '朝活ユーザー'], $mailService->sentTo);
    }

    public function test_rejects_a_duplicate_email_address_without_saving_or_sending(): void
    {
        $accountRepository = new InMemoryAccountRepository;
        $accountRepository->existingAccount = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('既存ユーザー'), null,
            new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]),
        );
        $mailService = new FakeAccountRegistrationMailService;

        $this->expectException(DuplicateEmailAddressException::class);

        (new CreateAccount(
            accountRepository: $accountRepository,
            accountFactory: new FakeAccountFactory($accountRepository->existingAccount),
            accountRegistrationMailService: $mailService,
            transactionManager: new ImmediateTransactionManager,
            storageService: new FakeStorageService,
        ))->execute(new CreateAccountInput(
            new AccountName('朝活ユーザー'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]),
        ));
    }
}

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    public ?Account $existingAccount = null;

    public ?Account $savedAccount = null;

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        return null;
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return $this->existingAccount;
    }

    public function save(Account $account): void
    {
        $this->savedAccount = $account;
    }
}
final readonly class FakeAccountFactory implements AccountFactoryInterface
{
    public function __construct(private Account $account) {}

    public function create(AccountName $accountName, ?AccountBio $accountBio, EmailAddress $emailAddress, array $socialLinks, FavoriteTagIdentifiers $favoriteTagIdentifiers): Account
    {
        return $this->account;
    }
}
final class FakeAccountRegistrationMailService implements AccountRegistrationMailServiceInterface
{
    /** @var array{string, string}|null */
    public ?array $sentTo = null;

    public function send(EmailAddress $emailAddress, AccountName $accountName): void
    {
        $this->sentTo = [$emailAddress->value(), $accountName->value()];
    }
}

final class FakeStorageService implements StorageServiceInterface
{
    public function uploadIcon(AccountIdentifier $accountIdentifier, AccountIcon $icon): void {}

    public function uploadHeader(AccountIdentifier $accountIdentifier, AccountHeader $header): void {}

    public function deleteIcon(AccountIdentifier $accountIdentifier): void {}

    public function deleteHeader(AccountIdentifier $accountIdentifier): void {}
}

final class ImmediateTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}
