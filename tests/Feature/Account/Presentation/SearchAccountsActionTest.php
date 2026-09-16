<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class SearchAccountsActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_active_accounts_whose_names_start_with_the_search_name_including_private_accounts(): void
    {
        $this->account('11111111-1111-4111-8111-111111111111', 'アリス', '公開プロフィール', 'public');
        $this->account('22222222-2222-4222-8222-222222222222', 'アリス秘密', '鍵プロフィール', 'private');
        $this->account('33333333-3333-4333-8333-333333333333', 'ボブ', '対象外', 'public');

        $this->getJson('/api/accounts/search?account_name=アリ&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertExactJson([
                'accounts' => [
                    [
                        'account_identifier' => '11111111-1111-4111-8111-111111111111',
                        'account_name' => 'アリス',
                        'account_bio' => '公開プロフィール',
                        'icon_image_url' => 'https://images.example/accounts/11111111-1111-4111-8111-111111111111/icon',
                    ],
                    [
                        'account_identifier' => '22222222-2222-4222-8222-222222222222',
                        'account_name' => 'アリス秘密',
                        'account_bio' => '鍵プロフィール',
                        'icon_image_url' => 'https://images.example/accounts/22222222-2222-4222-8222-222222222222/icon',
                    ],
                ],
                'total' => 2,
            ]);
    }

    public function test_returns_only_accounts_that_have_all_specified_favorite_tags(): void
    {
        $tagOneIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $tagTwoIdentifier = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $this->tag($tagOneIdentifier, '朝活');
        $this->tag($tagTwoIdentifier, '運動');
        $this->account('11111111-1111-4111-8111-111111111111', '両方のタグ', '対象', 'public');
        $this->account('22222222-2222-4222-8222-222222222222', '片方のタグ', '対象外', 'public');
        $this->favoriteTag('11111111-1111-4111-8111-111111111111', $tagOneIdentifier);
        $this->favoriteTag('11111111-1111-4111-8111-111111111111', $tagTwoIdentifier);
        $this->favoriteTag('22222222-2222-4222-8222-222222222222', $tagOneIdentifier);

        $this->getJson("/api/accounts/search?tag_identifiers[]={$tagOneIdentifier}&tag_identifiers[]={$tagTwoIdentifier}")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('accounts.0.account_identifier', '11111111-1111-4111-8111-111111111111');
    }

    public function test_excludes_unavailable_inactive_and_blocked_accounts(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->account($viewerIdentifier, '閲覧者', '検索者', 'public');
        $this->account('22222222-2222-4222-8222-222222222222', '対象アカウント', '表示する', 'public');
        $this->account('33333333-3333-4333-8333-333333333333', '対象停止中', '表示しない', 'public', 'temporarily_banned');
        $this->account('44444444-4444-4444-8444-444444444444', '対象ブロック中', '表示しない', 'public');
        $this->account('55555555-5555-4555-8555-555555555555', '対象利用停止', '表示しない', 'public', 'active', false);
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $viewerIdentifier,
            'blocked_account_identifier' => '44444444-4444-4444-8444-444444444444',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/accounts/search?account_name=')
            ->assertUnprocessable();

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/accounts/search?account_name=対象&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertExactJson([
                'accounts' => [[
                    'account_identifier' => '22222222-2222-4222-8222-222222222222',
                    'account_name' => '対象アカウント',
                    'account_bio' => '表示する',
                    'icon_image_url' => 'https://images.example/accounts/22222222-2222-4222-8222-222222222222/icon',
                ]],
                'total' => 1,
            ]);
    }

    public function test_requires_a_search_condition_and_valid_tag_identifiers(): void
    {
        $this->getJson('/api/accounts/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_name', 'tag_identifiers']);

        $this->getJson('/api/accounts/search?tag_identifiers[]=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_identifiers.0']);
    }

    public function test_paginates_account_search_results(): void
    {
        $this->account('11111111-1111-4111-8111-111111111111', 'ページ一', '1件目', 'public');
        $this->account('22222222-2222-4222-8222-222222222222', 'ページ二', '2件目', 'public');

        $this->getJson('/api/accounts/search?account_name=ページ&page=2&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('accounts.0.account_identifier', '22222222-2222-4222-8222-222222222222')
            ->assertJsonCount(1, 'accounts');
    }

    private function account(string $identifier, string $name, string $bio, string $visibility, string $status = 'active', bool $available = true): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => $bio,
            'email_address' => "$identifier@example.com",
            'available' => $available,
            'status' => $status,
            'visibility' => $visibility,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function favoriteTag(string $accountIdentifier, string $tagIdentifier): void
    {
        DB::table('favorite_tags')->insert([
            'account_identifier' => $accountIdentifier,
            'tag_identifier' => $tagIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
