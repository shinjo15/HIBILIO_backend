<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetPopularRoutinePostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_active_routine_posts_from_the_last_seven_days_in_like_count_order(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $blockedAuthorIdentifier = '33333333-3333-4333-8333-333333333333';
        $inactiveAuthorIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->insertAccount($viewerIdentifier, '閲覧者', 'active');
        $this->insertAccount($authorIdentifier, '投稿者', 'active');
        $this->insertAccount($blockedAuthorIdentifier, 'Block対象', 'active');
        $this->insertAccount($inactiveAuthorIdentifier, '停止中投稿者', 'temporarilyBan');
        $this->insertRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $authorIdentifier, '人気Routine', 30);
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $authorIdentifier, '同率で新しいRoutine', 20);
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $authorIdentifier, '同率で古いRoutine', 15);
        $this->insertRoutine('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $authorIdentifier, '期間外Routine', 10);
        $this->insertRoutine('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $blockedAuthorIdentifier, 'Block済みRoutine', 10);
        $this->insertRoutine('ffffffff-ffff-4fff-8fff-ffffffffffff', $inactiveAuthorIdentifier, '停止中Routine', 10);
        $this->insertPost('12121212-1212-4121-8121-121212121212', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'routine', 20, now()->subDay());
        $this->insertPost('13131313-1313-4131-8131-131313131313', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', 10, now()->subHour());
        $this->insertPost('14141414-1414-4141-8141-141414141414', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine', 10, now()->subDays(2));
        $this->insertPost('15151515-1515-4151-8151-151515151515', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'routine', 100, now()->subDays(7)->subSecond());
        $this->insertPost('16161616-1616-4161-8161-161616161616', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'action', 100, now());
        $this->insertPost('17171717-1717-4171-8171-171717171717', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'routine', 100, now());
        $this->insertPost('18181818-1818-4181-8181-181818181818', 'ffffffff-ffff-4fff-8fff-ffffffffffff', 'routine', 100, now());
        $this->insertBlock($viewerIdentifier, $blockedAuthorIdentifier);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/posts/popular?page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('posts.0.post_identifier', '12121212-1212-4121-8121-121212121212')
            ->assertJsonPath('posts.1.post_identifier', '13131313-1313-4131-8131-131313131313')
            ->assertJsonPath('posts.2.post_identifier', '14141414-1414-4141-8141-141414141414')
            ->assertJsonPath('posts.0.post_like_count', 20)
            ->assertJsonCount(3, 'posts');
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/posts/popular?page=1&number_of_items_per_page=20')->assertUnauthorized();
    }

    public function test_requires_pagination_parameters(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/posts/popular')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function insertAccount(string $identifier, string $name, string $status): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'email_address' => "{$identifier}@example.com", 'available' => true, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier, string $name, int $minutes): void
    {
        DB::table('routines')->insert(['routine_identifier' => $identifier, 'parent_routine_identifier' => null, 'account_identifier' => $accountIdentifier, 'routine_name' => $name, 'routine_execution_minutes' => $minutes, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertPost(string $identifier, string $routineIdentifier, string $category, int $likes, mixed $createdAt): void
    {
        DB::table('posts')->insert(['post_identifier' => $identifier, 'routine_identifier' => $routineIdentifier, 'post_category' => $category, 'post_like_count' => $likes, 'post_support_count' => 0, 'available' => true, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }

    private function insertBlock(string $blockingAccountIdentifier, string $blockedAccountIdentifier): void
    {
        DB::table('blocks')->insert(['blocking_account_identifier' => $blockingAccountIdentifier, 'blocked_account_identifier' => $blockedAccountIdentifier, 'created_at' => now(), 'updated_at' => now()]);
    }
}
