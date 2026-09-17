<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetAccountRoutineExecutionsActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_an_empty_history_for_an_account_without_routine_executions(): void
    {
        $this->getJson('/api/accounts/11111111-1111-4111-8111-111111111111/routine-executions')
            ->assertOk()
            ->assertExactJson(['items' => [], 'total' => 0]);
    }

    public function test_returns_execution_history_with_executor_account_fields(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineExecutionIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier);

        $this->getJson("/api/accounts/{$accountIdentifier}/routine-executions")
            ->assertOk()
            ->assertExactJson($this->expectedResponse($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier));
    }

    public function test_returns_the_logged_in_accounts_execution_history_with_executor_account_fields(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineExecutionIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier);

        $this->withSession(['account_identifier' => $accountIdentifier])
            ->getJson('/api/my/routine-executions')
            ->assertOk()
            ->assertExactJson($this->expectedResponse($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier));
    }

    private function expectedResponse(string $accountIdentifier, string $routineIdentifier, string $routineExecutionIdentifier): array
    {
        return ['items' => [[
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'routine_identifier' => $routineIdentifier,
            'routine_name' => '朝活',
            'account_identifier' => $accountIdentifier,
            'account_name' => '実行者',
            'icon_image_url' => "https://images.example/accounts/{$accountIdentifier}/icon",
            'executed_action_count' => 0,
            'posted_at' => '2026-09-07T10:00:00+00:00',
            'routine_execution_memo' => '振り返り',
            'support_count' => 7,
            'liked' => false,
            'supported' => false,
        ]], 'total' => 1];
    }

    private function createExecutionHistory(string $accountIdentifier, string $routineIdentifier, string $routineExecutionIdentifier): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => '実行者',
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
        DB::table('routines')->insert([
            'routine_identifier' => $routineIdentifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => '朝活',
            'available' => true,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'executor_account_identifier' => $accountIdentifier,
            'routine_identifier' => $routineIdentifier,
            'executed_at' => '2026-09-07 10:00:00',
            'routine_execution_memo' => '振り返り',
            'created_at' => '2026-09-07 10:00:00',
            'updated_at' => '2026-09-07 10:00:00',
        ]);
        DB::table('posts')->insert([
            'post_identifier' => '44444444-4444-4444-8444-444444444444',
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => 'action',
            'post_like_count' => 0,
            'post_support_count' => 7,
            'available' => true,
            'created_at' => '2026-09-07 10:00:00',
            'updated_at' => '2026-09-07 10:00:00',
        ]);
    }
}
