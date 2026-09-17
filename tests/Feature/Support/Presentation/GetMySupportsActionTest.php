<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetMySupportsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_the_session_accounts_supports_with_requested_number_of_items_per_page_and_total(): void
    {
        $routineExecutionIdentifier = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';

        $this->createAccount('33333333-3333-4333-8333-333333333333');
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createRoutineExecution($routineExecutionIdentifier, '11111111-1111-4111-8111-111111111111');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', null, 'action', 3, 8);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', $routineExecutionIdentifier, 'action', 2, 4);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', null, 'action', 1, 6);
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 12:00:00');
        $this->createSupport('44444444-4444-4444-8444-444444444444', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 13:00:00');

        $response = $this->withSession(['account_identifier' => '33333333-3333-4333-8333-333333333333'])
            ->getJson('/api/my/supports?page=1&number_of_items_per_page=1&account_identifier=44444444-4444-4444-8444-444444444444');

        $response->assertOk()
            ->assertJsonCount(1, 'supports')
            ->assertJson([
                'supports' => [[
                    'post_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                    'routine_identifier' => '11111111-1111-4111-8111-111111111111',
                    'routine_execution_identifier' => $routineExecutionIdentifier,
                    'post_category' => 'action',
                    'post_like_count' => 2,
                    'post_support_count' => 4,
                    'supported' => true,
                    'supported_at' => '2026-09-03T12:00:00+00:00',
                ]],
                'total' => 2,
            ]);
    }

    public function test_returns_unauthorized_when_the_session_has_no_account_identifier(): void
    {
        $this->getJson('/api/my/supports?page=1&number_of_items_per_page=1')->assertUnauthorized();
    }

    private function createAccount(string $accountIdentifier): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => 'Support実行者',
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => '2026-09-03 09:00:00',
            'updated_at' => '2026-09-03 09:00:00',
        ]);
    }

    private function createRoutine(string $routineIdentifier): void
    {
        DB::table('routines')->insert(['routine_identifier' => $routineIdentifier, 'routine_name' => '朝活', 'account_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_execution_minutes' => 1, 'available' => true, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createRoutineExecution(string $routineExecutionIdentifier, string $routineIdentifier): void
    {
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $routineExecutionIdentifier, 'executor_account_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_identifier' => $routineIdentifier, 'executed_at' => '2026-09-03 09:00:00', 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createPost(string $postIdentifier, string $routineIdentifier, ?string $routineExecutionIdentifier, string $postCategory, int $postLikeCount, int $postSupportCount): void
    {
        DB::table('posts')->insert(['post_identifier' => $postIdentifier, 'routine_identifier' => $routineIdentifier, 'routine_execution_identifier' => $routineExecutionIdentifier, 'post_category' => $postCategory, 'post_like_count' => $postLikeCount, 'post_support_count' => $postSupportCount, 'available' => true, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createSupport(string $accountIdentifier, string $postIdentifier, string $createdAt): void
    {
        DB::table('supports')->insert(['account_identifier' => $accountIdentifier, 'post_identifier' => $postIdentifier, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }
}
