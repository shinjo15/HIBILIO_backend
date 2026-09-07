<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetFollowingPostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_followed_accounts_routine_and_action_posts_in_descending_post_datetime_order(): void
    {
        $followingAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $followedAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $unfollowedAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $routineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        $this->insertAccount($followingAccountIdentifier, 'フォロー実行者');
        $this->insertAccount($followedAccountIdentifier, 'フォロー対象者');
        $this->insertAccount($unfollowedAccountIdentifier, '対象外');
        $this->insertFollow($followingAccountIdentifier, $followedAccountIdentifier);
        $this->insertRoutine($routineIdentifier, $followedAccountIdentifier, null, '朝の集中ルーティン', 40);
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $followedAccountIdentifier, $routineIdentifier, 'カスタマイズ版', 30);
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $unfollowedAccountIdentifier, null, '対象外ルーティン', 10);
        $this->insertTag('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '朝活');
        $this->insertRoutineTag($routineIdentifier, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
        $this->insertRoutineAction('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $routineIdentifier, '水を飲む', 1);
        $this->insertRoutineAction('ffffffff-ffff-4fff-8fff-ffffffffffff', $routineIdentifier, 'ストレッチ', 5);
        $this->insertPost('12121212-1212-4121-8121-121212121212', $routineIdentifier, 'routine', 4, '2026-09-04 10:00:00');
        $this->insertPost('13131313-1313-4131-8131-131313131313', $routineIdentifier, 'action', 0, '2026-09-04 11:00:00');
        $this->insertPost('14141414-1414-4141-8141-141414141414', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine', 9, '2026-09-04 12:00:00');
        $this->insertLike($followingAccountIdentifier, '13131313-1313-4131-8131-131313131313');

        $this->withSession(['account_identifier' => $followingAccountIdentifier])
            ->getJson('/api/following/posts?page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJson([
                'posts' => [
                    [
                        'post_identifier' => '13131313-1313-4131-8131-131313131313',
                        'routine_identifier' => $routineIdentifier,
                        'post_category' => 'action',
                        'account_identifier' => $followedAccountIdentifier,
                        'account_name' => 'フォロー対象者',
                        'posted_at' => '2026-09-04T11:00:00+00:00',
                        'routine_name' => '朝の集中ルーティン',
                        'routine_execution_minutes' => 40,
                        'tags' => [[
                            'tag_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
                            'tag_name' => '朝活',
                        ]],
                        'routine_actions' => [
                            [
                                'routine_action_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
                                'action_name' => '水を飲む',
                                'action_minutes' => 1,
                            ],
                            [
                                'routine_action_identifier' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                                'action_name' => 'ストレッチ',
                                'action_minutes' => 5,
                            ],
                        ],
                        'post_like_count' => 0,
                        'liked' => true,
                        'execution_count' => 1,
                        'customization_count' => 1,
                    ],
                    [
                        'post_identifier' => '12121212-1212-4121-8121-121212121212',
                        'routine_identifier' => $routineIdentifier,
                        'post_category' => 'routine',
                        'account_identifier' => $followedAccountIdentifier,
                        'account_name' => 'フォロー対象者',
                        'posted_at' => '2026-09-04T10:00:00+00:00',
                        'routine_name' => '朝の集中ルーティン',
                        'routine_execution_minutes' => 40,
                        'post_like_count' => 4,
                        'liked' => false,
                        'execution_count' => 1,
                        'customization_count' => 1,
                    ],
                ],
                'total' => 2,
            ]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/following/posts')->assertUnauthorized();
    }

    public function test_returns_validation_errors_for_invalid_pagination_parameters(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/following/posts?page=0&number_of_items_per_page=zero')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function insertAccount(string $accountIdentifier, string $accountName): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => $accountName,
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertFollow(string $followingAccountIdentifier, string $followedAccountIdentifier): void
    {
        DB::table('follows')->insert([
            'following_account_identifier' => $followingAccountIdentifier,
            'followed_account_identifier' => $followedAccountIdentifier,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertRoutine(
        string $routineIdentifier,
        string $accountIdentifier,
        ?string $parentRoutineIdentifier,
        string $routineName,
        int $routineExecutionMinutes,
    ): void {
        DB::table('routines')->insert([
            'routine_identifier' => $routineIdentifier,
            'parent_routine_identifier' => $parentRoutineIdentifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $routineName,
            'routine_execution_minutes' => $routineExecutionMinutes,
            'available' => true,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertTag(string $tagIdentifier, string $tagName): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $tagIdentifier,
            'tag_name' => $tagName,
            'available' => true,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertRoutineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertRoutineAction(
        string $routineActionIdentifier,
        string $routineIdentifier,
        string $actionName,
        int $actionMinutes,
    ): void {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $routineActionIdentifier,
            'routine_identifier' => $routineIdentifier,
            'action_name' => $actionName,
            'action_minutes' => $actionMinutes,
            'available' => true,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }

    private function insertPost(
        string $postIdentifier,
        string $routineIdentifier,
        string $postCategory,
        int $postLikeCount,
        string $createdAt,
    ): void {
        DB::table('posts')->insert([
            'post_identifier' => $postIdentifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => $postCategory,
            'post_like_count' => $postLikeCount,
            'post_support_count' => 0,
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function insertLike(string $accountIdentifier, string $postIdentifier): void
    {
        DB::table('likes')->insert([
            'account_identifier' => $accountIdentifier,
            'post_identifier' => $postIdentifier,
            'created_at' => '2026-09-04 09:00:00',
            'updated_at' => '2026-09-04 09:00:00',
        ]);
    }
}
