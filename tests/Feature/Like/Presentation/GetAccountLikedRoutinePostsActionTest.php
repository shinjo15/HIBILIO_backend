<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAccountLikedRoutinePostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_requested_accounts_liked_routine_posts_in_liked_at_descending_order(): void
    {
        $likingAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->createAccount($likingAccountIdentifier, 'いいねした人');
        $this->createAccount($routineAuthorIdentifier, 'Routine作成者');
        $this->createRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $routineAuthorIdentifier, '朝のストレッチ', null);
        $this->createRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $routineAuthorIdentifier, '夜のストレッチ', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'routine', true, 3, 8, '2026-09-03 09:00:00');
        $this->createPost('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', true, 2, 4, '2026-09-03 10:00:00');
        $this->createLike($likingAccountIdentifier, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-09-03 11:00:00');
        $this->createLike($likingAccountIdentifier, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '2026-09-03 12:00:00');
        $this->createTag('ffffffff-ffff-4fff-8fff-ffffffffffff', 'ストレッチ');
        $this->createRoutineTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'ffffffff-ffff-4fff-8fff-ffffffffffff');
        $this->createRoutineAction('99999999-9999-4999-8999-999999999999', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '深呼吸', 5);

        $this->getJson("/api/accounts/{$likingAccountIdentifier}/likes?page=1&number_of_items_per_page=1")
            ->assertOk()
            ->assertExactJson([
                'items' => [[
                    'post_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
                    'routine_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                    'account_identifier' => $routineAuthorIdentifier,
                    'account_name' => 'Routine作成者',
                    'posted_at' => '2026-09-03T10:00:00+00:00',
                    'routine_name' => '夜のストレッチ',
                    'routine_execution_minutes' => 10,
                    'tags' => [[
                        'tag_identifier' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                        'tag_name' => 'ストレッチ',
                    ]],
                    'routine_actions' => [[
                        'routine_action_identifier' => '99999999-9999-4999-8999-999999999999',
                        'action_name' => '深呼吸',
                        'action_minutes' => 5,
                    ]],
                    'post_like_count' => 2,
                    'post_support_count' => 4,
                    'execution_count' => 0,
                    'customization_count' => 0,
                    'liked_at' => '2026-09-03T12:00:00+00:00',
                ]],
                'total' => 2,
            ]);
    }

    public function test_excludes_action_posts_and_unavailable_routines_posts_and_inactive_authors(): void
    {
        $likingAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $activeAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $inactiveAuthorIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createAccount($likingAccountIdentifier, 'いいねした人');
        $this->createAccount($activeAuthorIdentifier, '公開作成者');
        $this->createAccount($inactiveAuthorIdentifier, '停止作成者', true, 'temporarily_banned');
        $this->createRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $activeAuthorIdentifier, '公開Routine');
        $this->createRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $activeAuthorIdentifier, '非公開Routine', null, false);
        $this->createRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $inactiveAuthorIdentifier, '停止中Routine');
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'routine', true, 0, 0, '2026-09-03 09:00:00');
        $this->createPost('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'action', true, 0, 0, '2026-09-03 09:00:00');
        $this->createPost('ffffffff-ffff-4fff-8fff-ffffffffffff', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', true, 0, 0, '2026-09-03 09:00:00');
        $this->createPost('99999999-9999-4999-8999-999999999999', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine', true, 0, 0, '2026-09-03 09:00:00');
        foreach (['dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'ffffffff-ffff-4fff-8fff-ffffffffffff', '99999999-9999-4999-8999-999999999999'] as $postIdentifier) {
            $this->createLike($likingAccountIdentifier, $postIdentifier, '2026-09-03 12:00:00');
        }

        $this->getJson("/api/accounts/{$likingAccountIdentifier}/likes")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.post_identifier', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd')
            ->assertJsonPath('total', 1);
    }

    public function test_returns_the_session_accounts_liked_routine_posts(): void
    {
        $likingAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->createAccount($likingAccountIdentifier, 'いいねした人');
        $this->createAccount($routineAuthorIdentifier, 'Routine作成者');
        $this->createRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $routineAuthorIdentifier, '朝活');
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'routine', true, 0, 0, '2026-09-03 09:00:00');
        $this->createLike($likingAccountIdentifier, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-09-03 12:00:00');

        $this->withSession(['account_identifier' => $likingAccountIdentifier])
            ->getJson('/api/my/likes')
            ->assertOk()
            ->assertJsonPath('items.0.post_identifier', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
    }

    public function test_returns_validation_errors_for_invalid_pagination_parameters(): void
    {
        $this->getJson('/api/accounts/11111111-1111-4111-8111-111111111111/likes?page=0&number_of_items_per_page=zero')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function createAccount(string $identifier, string $name, bool $available = true, string $status = 'active'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRoutine(string $identifier, string $accountIdentifier, string $name, ?string $parentRoutineIdentifier = null, bool $available = true): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'parent_routine_identifier' => $parentRoutineIdentifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $name,
            'routine_execution_minutes' => 10,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPost(string $identifier, string $routineIdentifier, string $category, bool $available, int $likeCount, int $supportCount, string $createdAt): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => $category,
            'post_like_count' => $likeCount,
            'post_support_count' => $supportCount,
            'available' => $available,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createLike(string $accountIdentifier, string $postIdentifier, string $createdAt): void
    {
        DB::table('likes')->insert([
            'account_identifier' => $accountIdentifier,
            'post_identifier' => $postIdentifier,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createTag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRoutineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createRoutineAction(string $identifier, string $routineIdentifier, string $name, int $minutes): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'action_name' => $name,
            'action_minutes' => $minutes,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
