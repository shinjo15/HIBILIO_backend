<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetFavoriteTagPostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_unique_active_routine_posts_matching_favorite_tags_in_recommendation_order(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $blockedAuthorIdentifier = '33333333-3333-4333-8333-333333333333';
        $inactiveAuthorIdentifier = '44444444-4444-4444-8444-444444444444';
        $tagOneIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $tagTwoIdentifier = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

        $this->insertAccount($viewerIdentifier, '閲覧者', 'active');
        $this->insertAccount($authorIdentifier, 'おすすめ投稿者', 'active');
        $this->insertAccount($blockedAuthorIdentifier, 'Block対象', 'active');
        $this->insertAccount($inactiveAuthorIdentifier, '停止中', 'temporarilyBan');
        $this->insertTag($tagOneIdentifier, '朝活');
        $this->insertTag($tagTwoIdentifier, '運動');
        $this->insertFavoriteTag($viewerIdentifier, $tagOneIdentifier);
        $this->insertFavoriteTag($viewerIdentifier, $tagTwoIdentifier);
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $authorIdentifier, '両方一致', 30);
        $this->insertRoutine('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $authorIdentifier, '片方一致', 20);
        $this->insertRoutine('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $blockedAuthorIdentifier, 'Block済み', 10);
        $this->insertRoutine('ffffffff-ffff-4fff-8fff-ffffffffffff', $inactiveAuthorIdentifier, '停止中投稿者', 10);
        $this->insertRoutineTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $tagOneIdentifier);
        $this->insertRoutineTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $tagTwoIdentifier);
        $this->insertRoutineTag('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $tagOneIdentifier);
        $this->insertRoutineTag('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $tagOneIdentifier);
        $this->insertRoutineTag('ffffffff-ffff-4fff-8fff-ffffffffffff', $tagOneIdentifier);
        $this->insertPost('12121212-1212-4121-8121-121212121212', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine', 25, 0, now()->subHours(24));
        $this->insertPost('13131313-1313-4131-8131-131313131313', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'routine', 0, 0, now());
        $this->insertPost('14141414-1414-4141-8141-141414141414', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'action', 100, 100, now());
        $this->insertPost('15151515-1515-4151-8151-151515151515', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'routine', 100, 100, now());
        $this->insertPost('16161616-1616-4161-8161-161616161616', 'ffffffff-ffff-4fff-8fff-ffffffffffff', 'routine', 100, 100, now());
        $this->insertBlock($blockedAuthorIdentifier, $viewerIdentifier);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/posts/favorite_tags?page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('posts.0.post_identifier', '13131313-1313-4131-8131-131313131313')
            ->assertJsonPath('posts.0.post_support_count', 0)
            ->assertJsonPath('posts.1.post_identifier', '12121212-1212-4121-8121-121212121212')
            ->assertJsonCount(2, 'posts');
    }

    public function test_returns_an_empty_result_when_the_authenticated_account_has_no_favorite_tags(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($viewerIdentifier, '閲覧者', 'active');

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/posts/favorite_tags?page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertExactJson(['posts' => [], 'total' => 0]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/posts/favorite_tags?page=1&number_of_items_per_page=20')->assertUnauthorized();
    }

    public function test_requires_pagination_parameters(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/posts/favorite_tags')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function insertAccount(string $identifier, string $name, string $status): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'email_address' => "{$identifier}@example.com", 'available' => true, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertTag(string $identifier, string $name): void
    {
        DB::table('tags')->insert(['tag_identifier' => $identifier, 'tag_name' => $name, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertFavoriteTag(string $accountIdentifier, string $tagIdentifier): void
    {
        DB::table('favorite_tags')->insert(['account_identifier' => $accountIdentifier, 'tag_identifier' => $tagIdentifier, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier, string $name, int $minutes): void
    {
        DB::table('routines')->insert(['routine_identifier' => $identifier, 'parent_routine_identifier' => null, 'account_identifier' => $accountIdentifier, 'routine_name' => $name, 'routine_execution_minutes' => $minutes, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertRoutineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert(['routine_identifier' => $routineIdentifier, 'tag_identifier' => $tagIdentifier, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertPost(string $identifier, string $routineIdentifier, string $category, int $likes, int $supports, mixed $createdAt): void
    {
        DB::table('posts')->insert(['post_identifier' => $identifier, 'routine_identifier' => $routineIdentifier, 'post_category' => $category, 'post_like_count' => $likes, 'post_support_count' => $supports, 'available' => true, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }

    private function insertBlock(string $blockingAccountIdentifier, string $blockedAccountIdentifier): void
    {
        DB::table('blocks')->insert(['blocking_account_identifier' => $blockingAccountIdentifier, 'blocked_account_identifier' => $blockedAccountIdentifier, 'created_at' => now(), 'updated_at' => now()]);
    }
}
