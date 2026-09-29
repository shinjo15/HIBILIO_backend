<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Account\Domain\ValueObject\SocialType;
use Src\Account\Domain\ValueObject\SocialUrl;
use Src\Account\Infrastructure\Repository\AccountRepository;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;
use Tests\TestCase;

final class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_social_links_with_their_type_url_and_order_and_restores_them(): void
    {
        $this->insertTag();
        $account = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('朝活ユーザー'),
            new AccountBio('朝の時間を大切にしています。'), new EmailAddress('user@example.com'),
            [
                new SocialLink(SocialType::INSTAGRAM, new SocialUrl('https://instagram.com/example')),
                new SocialLink(SocialType::X, new SocialUrl('https://x.com/example')),
            ],
            new FavoriteTagIdentifiers([new TagIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5')]),
        );
        $repository = new AccountRepository;

        $repository->save($account);
        $restoredAccount = $repository->findByEmailAddress(new EmailAddress('user@example.com'));

        $this->assertDatabaseHas('account_social_links', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'type' => 'instagram',
            'url' => 'https://instagram.com/example',
            'position' => 0,
        ]);
        $this->assertDatabaseHas('account_social_links', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'type' => 'x',
            'url' => 'https://x.com/example',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('favorite_tags', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'tag_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
        ]);
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $restoredAccount?->accountIdentifier()->value());
        self::assertSame('朝の時間を大切にしています。', $restoredAccount?->accountBio()?->value());
        self::assertSame(['instagram', 'x'], array_map(static fn (SocialLink $socialLink): string => $socialLink->socialType()->value, $restoredAccount?->socialLinks() ?? []));
        self::assertSame(['https://instagram.com/example', 'https://x.com/example'], array_map(static fn (SocialLink $socialLink): string => $socialLink->socialUrl()->value(), $restoredAccount?->socialLinks() ?? []));
        self::assertSame(['b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'], array_map(static fn (TagIdentifier $identifier): string => $identifier->value(), $restoredAccount?->favoriteTagIdentifiers()->values() ?? []));
    }

    public function test_saves_and_restores_an_account_without_social_links(): void
    {
        $account = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('朝活ユーザー'), null,
            new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]),
        );

        (new AccountRepository)->save($account);
        $restoredAccount = (new AccountRepository)->findByEmailAddress(new EmailAddress('user@example.com'));

        $this->assertDatabaseCount('account_social_links', 0);
        $this->assertDatabaseCount('favorite_tags', 0);
        self::assertSame([], $restoredAccount?->socialLinks());
        self::assertSame([], $restoredAccount?->favoriteTagIdentifiers()->values());
    }

    public function test_database_rejects_duplicate_email_addresses(): void
    {
        $repository = new AccountRepository;
        $account = Account::create(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountName('朝活ユーザー'), null,
            new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]),
        );
        $repository->save($account);

        $this->expectException(UniqueConstraintViolationException::class);
        $repository->save(Account::create(
            new AccountIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7'), new AccountName('別ユーザー'), null,
            new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]),
        ));
    }

    public function test_restores_the_available_state_from_the_database(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'account_name' => 'Unavailable User',
            'email_address' => 'unavailable@example.com',
            'available' => false,
            'status' => 'active',
            'visibility' => 'public',
            'ui_mode' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = (new AccountRepository)->find(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'));

        self::assertNotNull($account);
        self::assertFalse($account->isAvailable());
    }

    private function insertTag(): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'tag_name' => '朝活',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
