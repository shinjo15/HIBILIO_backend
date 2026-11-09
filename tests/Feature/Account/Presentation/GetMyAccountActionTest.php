<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsOutput;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetMyAccountActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_authenticated_accounts_details(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($accountIdentifier, true, 'active', 'ログインアカウント', '自己紹介');
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '朝活');
        $this->insertFavoriteTag($accountIdentifier, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->insertSocialLink($accountIdentifier, 'x', 'https://x.com/example', 1);
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return '11111111-1111-4111-8111-111111111111';
            }
        });

        $this->getJson('/api/my/account')
            ->assertOk()
            ->assertExactJson([
                'account_identifier' => $accountIdentifier,
                'account_name' => 'ログインアカウント',
                'account_bio' => '自己紹介',
                'visibility' => 'public',
                'icon_image_url' => null,
                'header_image_url' => null,
                'ui_mode' => 'system',
                'like_notifications_enabled' => false,
                'support_notifications_enabled' => false,
                'favorite_tags' => [[
                    'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                    'tag_name' => '朝活',
                ]],
                'social_links' => [[
                    'social_type' => 'x',
                    'social_url' => 'https://x.com/example',
                ]],
            ]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/my/account')->assertUnauthorized();
    }

    #[DataProvider('missingNotificationSettingProvider')]
    public function test_fails_explicitly_when_the_query_omits_owner_notification_settings(string $missingKey): void
    {
        $identifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($identifier, true, 'active', 'アカウント', null);
        $details = [
            'accountIdentifier' => $identifier,
            'name' => 'アカウント',
            'isDetailed' => true,
            'bio' => null,
            'visibility' => 'public',
            'hasPendingFollowRequest' => false,
            'uiMode' => 'system',
            'favoriteTags' => [],
            'socialLinks' => [],
            'iconImageUrl' => null,
            'headerImageUrl' => null,
            'isFollowing' => false,
            'likeNotificationsEnabled' => false,
            'supportNotificationsEnabled' => false,
        ];
        unset($details[$missingKey]);
        $query = $this->createStub(GetAccountDetailsInterface::class);
        $query->method('execute')->willReturn(new GetAccountDetailsOutput($details));
        $this->app->instance(GetAccountDetailsInterface::class, $query);
        $this->withoutExceptionHandling();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Account owner notification settings are missing from query output.');

        $this->withSession(['account_identifier' => $identifier])->getJson('/api/my/account');
    }

    public static function missingNotificationSettingProvider(): array
    {
        return [
            'like' => ['likeNotificationsEnabled'],
            'support' => ['supportNotificationsEnabled'],
        ];
    }

    public function test_returns_full_details_for_the_authenticated_private_account(): void
    {
        $identifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($identifier, true, 'active', '鍵Account', '自己紹介');
        DB::table('accounts')->where('account_identifier', $identifier)->update(['visibility' => 'private']);

        $this->withSession(['account_identifier' => $identifier])
            ->getJson('/api/my/account')
            ->assertOk()
            ->assertExactJson([
                'account_identifier' => $identifier,
                'account_name' => '鍵Account',
                'account_bio' => '自己紹介',
                'visibility' => 'private',
                'icon_image_url' => null,
                'header_image_url' => null,
                'ui_mode' => 'system',
                'like_notifications_enabled' => false,
                'support_notifications_enabled' => false,
                'favorite_tags' => [],
                'social_links' => [],
            ]);
    }

    public function test_returns_both_notification_settings_for_every_combination(): void
    {
        $identifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($identifier, true, 'active', 'アカウント', null);

        foreach ([false, true] as $likeEnabled) {
            foreach ([false, true] as $supportEnabled) {
                DB::table('accounts')->where('account_identifier', $identifier)->update([
                    'like_notifications_enabled' => $likeEnabled,
                    'support_notifications_enabled' => $supportEnabled,
                ]);

                $this->withSession(['account_identifier' => $identifier])
                    ->getJson('/api/my/account')
                    ->assertOk()
                    ->assertExactJson([
                        'account_identifier' => $identifier,
                        'account_name' => 'アカウント',
                        'account_bio' => null,
                        'visibility' => 'public',
                        'icon_image_url' => null,
                        'header_image_url' => null,
                        'ui_mode' => 'system',
                        'like_notifications_enabled' => $likeEnabled,
                        'support_notifications_enabled' => $supportEnabled,
                        'favorite_tags' => [],
                        'social_links' => [],
                    ]);
            }
        }
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
