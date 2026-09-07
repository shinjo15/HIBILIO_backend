<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetRoutineExecutionPostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_execution_posts_with_exact_fields_using_default_pagination(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';
        $accountIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineExecutionIdentifier = '33333333-3333-4333-8333-333333333333';

        $this->insertAccount($accountIdentifier, '実行者');
        $this->insertRoutine($routineIdentifier);
        $this->insertRoutineExecution($routineExecutionIdentifier, $accountIdentifier, $routineIdentifier, '振り返りメモ');
        $this->insertRoutineAction('44444444-4444-4444-8444-444444444444', $routineIdentifier);
        $this->insertRoutineAction('55555555-5555-4555-8555-555555555555', $routineIdentifier);
        $this->insertRoutineExecutionAction($routineExecutionIdentifier, '44444444-4444-4444-8444-444444444444');
        $this->insertRoutineExecutionAction($routineExecutionIdentifier, '55555555-5555-4555-8555-555555555555');
        $this->insertPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $routineExecutionIdentifier, 7);

        $this->getJson("/api/routines/{$routineIdentifier}/execution-posts")
            ->assertOk()
            ->assertExactJson([
                'items' => [[
                    'account_identifier' => $accountIdentifier,
                    'account_name' => '実行者',
                    'executed_action_count' => 2,
                    'posted_at' => '2026-09-07T10:00:00+00:00',
                    'routine_execution_memo' => '振り返りメモ',
                    'support_count' => 7,
                ]],
                'total' => 1,
            ]);
    }

    public function test_returns_the_second_execution_post_and_total_with_requested_pagination(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';
        $firstAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $secondAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $firstRoutineExecutionIdentifier = '44444444-4444-4444-8444-444444444444';
        $secondRoutineExecutionIdentifier = '55555555-5555-4555-8555-555555555555';

        $this->insertAccount($firstAccountIdentifier, '1番目の実行者');
        $this->insertAccount($secondAccountIdentifier, '2番目の実行者');
        $this->insertRoutine($routineIdentifier);
        $this->insertRoutineExecution($firstRoutineExecutionIdentifier, $firstAccountIdentifier, $routineIdentifier, '1番目のメモ');
        $this->insertRoutineExecution($secondRoutineExecutionIdentifier, $secondAccountIdentifier, $routineIdentifier, '2番目のメモ');
        $this->insertPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $firstRoutineExecutionIdentifier, 7, '2026-09-07 10:00:00');
        $this->insertPost('77777777-7777-4777-8777-777777777777', $routineIdentifier, $secondRoutineExecutionIdentifier, 8, '2026-09-07 09:00:00');

        $this->getJson("/api/routines/{$routineIdentifier}/execution-posts?page=2&number_of_items_per_page=1")
            ->assertOk()
            ->assertExactJson([
                'items' => [[
                    'account_identifier' => $secondAccountIdentifier,
                    'account_name' => '2番目の実行者',
                    'executed_action_count' => 0,
                    'posted_at' => '2026-09-07T09:00:00+00:00',
                    'routine_execution_memo' => '2番目のメモ',
                    'support_count' => 8,
                ]],
                'total' => 2,
            ]);
    }

    public function test_returns_an_empty_list_for_an_unknown_routine_without_parent_existence_check(): void
    {
        $this->getJson('/api/routines/99999999-9999-4999-8999-999999999999/execution-posts?page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertExactJson([
                'items' => [],
                'total' => 0,
            ]);
    }

    public function test_returns_validation_errors_for_invalid_pagination_parameters(): void
    {
        $this->getJson('/api/routines/11111111-1111-4111-8111-111111111111/execution-posts?page=0&number_of_items_per_page=zero')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function insertAccount(string $identifier, string $name): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertRoutine(string $identifier): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'routine_name' => '朝活',
            'account_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'available' => true,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertRoutineExecution(string $identifier, string $accountIdentifier, string $routineIdentifier, string $memo): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'executor_account_identifier' => $accountIdentifier,
            'routine_identifier' => $routineIdentifier,
            'executed_at' => '2026-09-07 09:00:00',
            'routine_execution_memo' => $memo,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertRoutineExecutionAction(string $routineExecutionIdentifier, string $routineActionIdentifier): void
    {
        DB::table('routine_execution_actions')->insert([
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'routine_action_identifier' => $routineActionIdentifier,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertRoutineAction(string $identifier, string $routineIdentifier): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'action_name' => 'Action',
            'available' => true,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertPost(string $identifier, string $routineIdentifier, string $routineExecutionIdentifier, int $supportCount, string $createdAt = '2026-09-07 10:00:00'): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => 'action',
            'post_like_count' => 0,
            'post_support_count' => $supportCount,
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
