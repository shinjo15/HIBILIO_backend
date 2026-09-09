<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Application\Usecase\Command\UpdateAccountProfile;

use PHPUnit\Framework\TestCase;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Application\Usecase\Command\UpdateAccountProfile\UpdateAccountProfile;
use Src\Account\Application\Usecase\Command\UpdateAccountProfile\UpdateAccountProfileInput;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class UpdateAccountProfileTest extends TestCase
{
    public function test_persists_profile_changes_through_update_profile(): void
    {
        $account = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountName('更新前ユーザー'),
            null,
            new EmailAddress('user@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
        );
        $accountRepository = new InMemoryAccountRepository($account);

        (new UpdateAccountProfile(
            $accountRepository,
            new NullStorageService,
            new ImmediateTransactionManager,
        ))->execute(new UpdateAccountProfileInput(
            $account->accountIdentifier(),
            new AccountName('更新後ユーザー'),
            false,
            null,
            null,
            null,
            null,
            false,
            null,
            false,
        ));

        self::assertSame($account, $accountRepository->updatedProfileAccount);
        self::assertNull($accountRepository->savedAccount);
    }
}

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    public ?Account $updatedProfileAccount = null;

    public ?Account $savedAccount = null;

    public function __construct(private Account $account) {}

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        return $this->account;
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return null;
    }

    public function save(Account $account): void
    {
        $this->savedAccount = $account;
    }

    public function updateProfile(Account $account): void
    {
        $this->updatedProfileAccount = $account;
    }
}

final class NullStorageService implements StorageServiceInterface
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
