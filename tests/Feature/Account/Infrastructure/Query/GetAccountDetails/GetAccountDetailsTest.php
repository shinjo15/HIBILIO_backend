<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Query\GetAccountDetails;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
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
            'isDetailed' => true,
            'bio' => '自己紹介',
            'visibility' => 'public',
            'hasPendingFollowRequest' => false,
            'uiMode' => 'system',
            'favoriteTags' => [
                ['tagIdentifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'tagName' => '朝活'],
                ['tagIdentifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'tagName' => '朝活'],
                ['tagIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'tagName' => '運動'],
            ],
            'socialLinks' => [
                ['socialType' => 'instagram', 'socialUrl' => 'https://instagram.com/example'],
                ['socialType' => 'x', 'socialUrl' => 'https://x.com/example'],
            ],
            'iconImageUrl' => null,
            'headerImageUrl' => null,
            'isFollowing' => null,
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

    public function test_limits_private_account_details_to_followers_and_the_account_owner(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewerIdentifier, true, 'active', '閲覧者', null);
        $this->insertAccount($targetIdentifier, true, 'active', '鍵Account', '秘密の自己紹介', 'private');
        $query = $this->app->make(GetAccountDetailsInterface::class);

        self::assertNull($query->execute(new GetAccountDetailsInput($targetIdentifier))->accountDetails());
        self::assertSame([
            'accountIdentifier' => $targetIdentifier,
            'name' => '鍵Account',
            'isDetailed' => false,
            'visibility' => 'private',
            'hasPendingFollowRequest' => false,
        ], $query->execute(new GetAccountDetailsInput($targetIdentifier, $viewerIdentifier))->accountDetails());

        DB::table('follows')->insert([
            'following_account_identifier' => $viewerIdentifier,
            'followed_account_identifier' => $targetIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $followerDetails = $query->execute(new GetAccountDetailsInput($targetIdentifier, $viewerIdentifier))->accountDetails();
        self::assertTrue($followerDetails['isDetailed']);
        self::assertTrue($followerDetails['isFollowing']);
        self::assertSame('秘密の自己紹介', $followerDetails['bio']);
        $ownerDetails = $query->execute(new GetAccountDetailsInput($targetIdentifier, $targetIdentifier))->accountDetails();
        self::assertTrue($ownerDetails['isDetailed']);
        self::assertFalse($ownerDetails['isFollowing']);
        self::assertSame('秘密の自己紹介', $ownerDetails['bio']);
    }

    public function test_reverse_follow_does_not_expose_private_details_or_generate_image_urls(): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewer, true, 'active', '閲覧者', null);
        $this->insertAccount($target, true, 'active', '鍵Account', '秘密', 'private');
        DB::table('follows')->insert([
            'following_account_identifier' => $target,
            'followed_account_identifier' => $viewer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $images = $this->createMock(AccountImageUrlServiceInterface::class);
        $images->expects(self::never())->method('iconImageUrl');
        $images->expects(self::never())->method('headerImageUrl');
        $query = new GetAccountDetails($images);

        self::assertSame([
            'accountIdentifier' => $target,
            'name' => '鍵Account',
            'isDetailed' => false,
            'visibility' => 'private',
            'hasPendingFollowRequest' => false,
        ], $query->execute(new GetAccountDetailsInput($target, $viewer))->accountDetails());
        self::assertNull($query->execute(new GetAccountDetailsInput($target))->accountDetails());
    }

    private function insertAccount(string $identifier, bool $available, string $status, string $name, ?string $bio, string $visibility = 'public'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => $bio,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'visibility' => $visibility,
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
