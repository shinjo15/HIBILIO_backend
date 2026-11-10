<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AccountStatusTest extends TestCase
{
    public function test_new_account_is_active_without_a_ban_until(): void
    {
        $account = $this->account();

        self::assertSame(AccountStatus::ACTIVE, $account->status());
        self::assertNull($account->banUntil());
    }

    public function test_temporarily_ban_sets_a_ban_until_two_weeks_from_now(): void
    {
        $before = new DateTimeImmutable;
        $account = $this->account();
        $account->temporarilyBan();

        self::assertSame(AccountStatus::TEMPORARILY_BANNED, $account->status());
        self::assertEquals($before->modify('+2 weeks')->format('Y-m-d H:i'), $account->banUntil()?->format('Y-m-d H:i'));
    }

    public function test_active_and_permanently_ban_clear_the_ban_until(): void
    {
        $account = $this->account();
        $account->temporarilyBan();
        $account->permanentlyBan();

        self::assertSame(AccountStatus::PERMANENTLY_BANNED, $account->status());
        self::assertNull($account->banUntil());

        $account->active();

        self::assertSame(AccountStatus::ACTIVE, $account->status());
        self::assertNull($account->banUntil());
    }

    private function account(): Account
    {
        return Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountName('ユーザー'),
            null,
            new EmailAddress('user@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
        );
    }
}
