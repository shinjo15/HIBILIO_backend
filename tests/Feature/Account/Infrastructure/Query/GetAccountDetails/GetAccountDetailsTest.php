<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Query\GetAccountDetails;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInput;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Account\Infrastructure\Query\GetAccountDetails\GetAccountDetails;
use Tests\TestCase;

final class GetAccountDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_an_available_active_account_with_sorted_favorite_tags_and_social_links(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($accountIdentifier, true, 'active', '公開アカウント', '自己紹介');
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '運動');
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '朝活');
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '朝活');
        $this->insertFavoriteTag($accountIdentifier, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->insertFavoriteTag($accountIdentifier, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc');
        $this->insertFavoriteTag($accountIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->insertSocialLink($accountIdentifier, 'x', 'https://x.com/example', 2);
        $this->insertSocialLink($accountIdentifier, 'instagram', 'https://instagram.com/example', 1);

        $result = $this->app->make(GetAccountDetailsInterface::class)->execute(new GetAccountDetailsInput($accountIdentifier));

        self::assertInstanceOf(GetAccountDetails::class, $this->app->make(GetAccountDetailsInterface::class));
        self::assertSame([
            'accountIdentifier' => $accountIdentifier,
            'name' => '公開アカウント',
            'bio' => '自己紹介',
            'favoriteTags' => [
                ['tagIdentifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'tagName' => '朝活'],
                ['tagIdentifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'tagName' => '朝活'],
                ['tagIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'tagName' => '運動'],
            ],
            'socialLinks' => [
                ['socialType' => 'instagram', 'socialUrl' => 'https://instagram.com/example'],
                ['socialType' => 'x', 'socialUrl' => 'https://x.com/example'],
            ],
        ], $result->accountDetails());
    }

    public function test_returns_null_for_unavailable_or_non_active_accounts(): void
    {
        $unavailableAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $temporarilyBannedAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $permanentlyBannedAccountIdentifier = '44444444-4444-4444-8444-444444444444';
        $this->insertAccount($unavailableAccountIdentifier, false, 'active', '非公開', null);
        $this->insertAccount($temporarilyBannedAccountIdentifier, true, 'temporarily_banned', '一時停止', null);
        $this->insertAccount($permanentlyBannedAccountIdentifier, true, 'permanently_banned', '永久停止', null);

        $query = $this->app->make(GetAccountDetailsInterface::class);

        self::assertNull($query->execute(new GetAccountDetailsInput($unavailableAccountIdentifier))->accountDetails());
        self::assertNull($query->execute(new GetAccountDetailsInput($temporarilyBannedAccountIdentifier))->accountDetails());
        self::assertNull($query->execute(new GetAccountDetailsInput($permanentlyBannedAccountIdentifier))->accountDetails());
    }

    private function insertAccount(string $identifier, bool $available, string $status, string $name, ?string $bio): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => $bio,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertFavoriteTag(string $accountIdentifier, string $tagIdentifier): void
    {
        DB::table('favorite_tags')->insert([
            'account_identifier' => $accountIdentifier,
            'tag_identifier' => $tagIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSocialLink(string $accountIdentifier, string $type, string $url, int $position): void
    {
        DB::table('account_social_links')->insert([
            'account_identifier' => $accountIdentifier,
            'type' => $type,
            'url' => $url,
            'position' => $position,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
