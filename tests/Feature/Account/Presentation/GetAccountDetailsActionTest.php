<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetAccountDetailsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_anonymous_public_account_details_with_only_the_response_contract_fields(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($accountIdentifier, true, 'active', '公開アカウント', null);

        $this->getJson("/api/accounts/{$accountIdentifier}")
            ->assertOk()
            ->assertExactJson([
                'account_identifier' => $accountIdentifier,
                'account_name' => '公開アカウント',
                'account_bio' => null,
                'visibility' => 'public',
                'has_pending_follow_request' => false,
                'icon_image_url' => null,
                'header_image_url' => null,
                'favorite_tags' => [],
                'social_links' => [],
            ]);
    }

    public function test_returns_not_found_for_an_unavailable_or_non_active_account(): void
    {
        $unavailableAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $temporarilyBannedAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $permanentlyBannedAccountIdentifier = '44444444-4444-4444-8444-444444444444';
        $this->insertAccount($unavailableAccountIdentifier, false, 'active', '非公開', null);
        $this->insertAccount($temporarilyBannedAccountIdentifier, true, 'temporarily_banned', '一時停止', null);
        $this->insertAccount($permanentlyBannedAccountIdentifier, true, 'permanently_banned', '永久停止', null);

        $this->getJson("/api/accounts/{$unavailableAccountIdentifier}")->assertNotFound();
        $this->getJson("/api/accounts/{$temporarilyBannedAccountIdentifier}")->assertNotFound();
        $this->getJson("/api/accounts/{$permanentlyBannedAccountIdentifier}")->assertNotFound();
    }

    public function test_returns_not_found_to_both_sides_of_a_block_relationship_while_anonymous_access_remains_public(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewerIdentifier, true, 'active', '閲覧者', null);
        $this->insertAccount($targetIdentifier, true, 'active', '対象', null);
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $targetIdentifier,
            'blocked_account_identifier' => $viewerIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("/api/accounts/{$targetIdentifier}")->assertOk();
        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertNotFound();
        $this->withSession(['account_identifier' => $targetIdentifier])
            ->getJson("/api/accounts/{$viewerIdentifier}")
            ->assertNotFound();
    }

    public function test_returns_image_urls_without_exposing_storage_keys(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($accountIdentifier, true, 'active', '公開アカウント', null);
        $this->app->instance(AccountImageUrlServiceInterface::class, new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return 'https://images.example/icon';
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return null;
            }
        });

        $this->getJson("/api/accounts/{$accountIdentifier}")
            ->assertOk()
            ->assertJsonPath('icon_image_url', 'https://images.example/icon')
            ->assertJsonPath('header_image_url', null);
    }

    public function test_returns_the_minimum_private_account_details_with_pending_follow_request_state_for_an_authenticated_non_follower(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewerIdentifier, true, 'active', '申請者', null);
        $this->insertAccount($targetIdentifier, true, 'active', '鍵Account', '公開する自己紹介', 'private');
        DB::table('follow_requests')->insert([
            'requesting_account_identifier' => $viewerIdentifier,
            'target_account_identifier' => $targetIdentifier,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->app->instance(AccountImageUrlServiceInterface::class, new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return 'https://images.example/icon';
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return 'https://images.example/header';
            }
        });

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertExactJson([
                'account_identifier' => $targetIdentifier,
                'account_name' => '鍵Account',
                'account_bio' => '公開する自己紹介',
                'visibility' => 'private',
                'has_pending_follow_request' => true,
                'icon_image_url' => 'https://images.example/icon',
                'header_image_url' => 'https://images.example/header',
            ]);

        foreach (['approved', 'rejected'] as $status) {
            DB::table('follow_requests')->where([
                'requesting_account_identifier' => $viewerIdentifier,
                'target_account_identifier' => $targetIdentifier,
            ])->update(['status' => $status]);

            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson("/api/accounts/{$targetIdentifier}")
                ->assertExactJson([
                    'account_identifier' => $targetIdentifier,
                    'account_name' => '鍵Account',
                    'account_bio' => '公開する自己紹介',
                    'visibility' => 'private',
                    'has_pending_follow_request' => false,
                    'icon_image_url' => 'https://images.example/icon',
                    'header_image_url' => 'https://images.example/header',
                ]);
        }
    }

    public function test_returns_following_state_for_an_authenticated_viewer_of_a_public_account(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewerIdentifier, true, 'active', '閲覧者', null);
        $this->insertAccount($targetIdentifier, true, 'active', '対象', null);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertOk()
            ->assertJsonPath('is_following', false);

        DB::table('follows')->insert([
            'following_account_identifier' => $viewerIdentifier,
            'followed_account_identifier' => $targetIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertOk()
            ->assertJsonPath('is_following', true);
    }

    public function test_limits_private_account_response_based_on_the_viewer_relationship(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($viewerIdentifier, true, 'active', '閲覧者', null);
        $this->insertAccount($targetIdentifier, true, 'active', '鍵Account', '秘密の自己紹介', 'private');

        $this->getJson("/api/accounts/{$targetIdentifier}")->assertNotFound();
        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertExactJson([
                'account_identifier' => $targetIdentifier,
                'account_name' => '鍵Account',
                'account_bio' => '秘密の自己紹介',
                'visibility' => 'private',
                'has_pending_follow_request' => false,
                'icon_image_url' => null,
                'header_image_url' => null,
            ]);

        DB::table('follows')->insert([
            'following_account_identifier' => $viewerIdentifier,
            'followed_account_identifier' => $targetIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertOk()
            ->assertJsonPath('account_bio', '秘密の自己紹介')
            ->assertJsonPath('is_following', true);
        $this->withSession(['account_identifier' => $targetIdentifier])
            ->getJson("/api/accounts/{$targetIdentifier}")
            ->assertOk()
            ->assertJsonPath('account_bio', '秘密の自己紹介')
            ->assertJsonPath('is_following', false);
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
}
