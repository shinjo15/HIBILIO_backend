<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Account\Domain\ValueObject\SocialType;
use Src\Account\Domain\ValueObject\SocialUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class AccountTest extends TestCase
{
    public function test_restore_rejects_negative_experience(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Account::restore(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]), AccountStatus::ACTIVE, null, experience: -1);
    }

    public function test_new_account_starts_at_zero_experience_and_level_one(): void
    {
        $account = Account::create(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));

        self::assertTrue(method_exists($account, 'experience'));
        self::assertSame(0, $account->experience());
        self::assertSame(1, $account->level());
    }

    public function test_experience_increases_by_one_hundred_per_execution_and_levels_at_thresholds(): void
    {
        $account = Account::create(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));
        self::assertTrue(method_exists($account, 'recordRoutineExecution'));
        for ($i = 0; $i < 29; $i++) {
            $account->recordRoutineExecution();
        }
        self::assertSame(2900, $account->experience());
        self::assertSame(1, $account->level());
        $account->recordRoutineExecution();
        self::assertSame(3000, $account->experience());
        self::assertSame(2, $account->level());
    }

    public function test_level_boundaries(): void
    {
        self::assertSame(1, Account::levelForExperience(0));
        self::assertSame(1, Account::levelForExperience(2999));
        self::assertSame(2, Account::levelForExperience(3000));
        self::assertSame(2, Account::levelForExperience(5999));
        self::assertSame(3, Account::levelForExperience(6000));
    }

    public function test_level_rejects_negative_experience(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Account::levelForExperience(-3000);
    }

    public function test_like_notification_setting_defaults_off_and_can_be_changed(): void
    {
        $account = Account::create(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));
        self::assertFalse($account->likeNotificationsEnabled());
        $account->changeLikeNotifications(true);
        self::assertTrue($account->likeNotificationsEnabled());
        $account->changeLikeNotifications(false);
        self::assertFalse($account->likeNotificationsEnabled());
    }

    public function test_restore_preserves_both_notification_settings(): void
    {
        $account = Account::restore(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]), AccountStatus::ACTIVE, null, supportNotificationsEnabled: true, likeNotificationsEnabled: true);
        self::assertTrue($account->likeNotificationsEnabled());
        self::assertTrue($account->supportNotificationsEnabled());
    }

    public function test_creates_an_account_directly_from_valid_value_objects(): void
    {
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $accountName = new AccountName('朝活ユーザー');
        $accountBio = new AccountBio('朝の時間を大切にしています。');
        $emailAddress = new EmailAddress('user@example.com');
        $socialLinks = [
            new SocialLink(
                socialType: SocialType::X,
                socialUrl: new SocialUrl('https://x.com/example'),
            ),
        ];
        $favoriteTagIdentifiers = new FavoriteTagIdentifiers([
            new TagIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'),
        ]);

        $account = Account::create(
            accountIdentifier: $accountIdentifier,
            accountName: $accountName,
            accountBio: $accountBio,
            emailAddress: $emailAddress,
            socialLinks: $socialLinks,
            favoriteTagIdentifiers: $favoriteTagIdentifiers,
        );

        $this->assertSame($accountIdentifier, $account->accountIdentifier());
        $this->assertSame($accountName, $account->accountName());
        $this->assertSame($accountBio, $account->accountBio());
        $this->assertSame($emailAddress, $account->emailAddress());
        $this->assertSame($socialLinks, $account->socialLinks());
        $this->assertSame($favoriteTagIdentifiers, $account->favoriteTagIdentifiers());
        $this->assertTrue($account->isAvailable());
    }

    public function test_creates_an_account_without_a_bio(): void
    {
        $account = Account::create(
            accountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            accountName: new AccountName('朝活ユーザー'),
            accountBio: null,
            emailAddress: new EmailAddress('user@example.com'),
            socialLinks: [],
            favoriteTagIdentifiers: new FavoriteTagIdentifiers([]),
        );

        $this->assertNull($account->accountBio());
    }

    public function test_rejects_social_links_that_are_not_a_list(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Account::create(
            accountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            accountName: new AccountName('朝活ユーザー'),
            accountBio: null,
            emailAddress: new EmailAddress('user@example.com'),
            socialLinks: ['first' => new \stdClass],
            favoriteTagIdentifiers: new FavoriteTagIdentifiers([]),
        );
    }

    public function test_rejects_a_social_link_that_is_not_a_social_link_value_object(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Account::create(
            accountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            accountName: new AccountName('朝活ユーザー'),
            accountBio: null,
            emailAddress: new EmailAddress('user@example.com'),
            socialLinks: [new \stdClass],
            favoriteTagIdentifiers: new FavoriteTagIdentifiers([]),
        );
    }

    public function test_restores_an_unavailable_account(): void
    {
        $account = Account::restore(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountName('朝活ユーザー'),
            null,
            new EmailAddress('user@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
            AccountStatus::ACTIVE,
            null,
            available: false,
        );

        $this->assertFalse($account->isAvailable());
    }
}
