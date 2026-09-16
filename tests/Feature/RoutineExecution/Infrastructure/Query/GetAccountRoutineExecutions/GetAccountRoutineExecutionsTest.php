<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Infrastructure\Query\GetAccountRoutineExecutions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInput;
use Src\RoutineExecution\Infrastructure\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutions;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetAccountRoutineExecutionsTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_it_returns_executor_account_fields_and_icon_image_url(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineExecutionIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier);

        $result = $this->app->make(GetAccountRoutineExecutions::class)
            ->execute(new GetAccountRoutineExecutionsInput($accountIdentifier, 1, 20));

        self::assertSame([[
            'routineExecutionIdentifier' => $routineExecutionIdentifier,
            'routineIdentifier' => $routineIdentifier,
            'routineName' => '朝活',
            'accountIdentifier' => $accountIdentifier,
            'accountName' => '実行者',
            'iconImageUrl' => "https://images.example/accounts/{$accountIdentifier}/icon",
            'executedActionCount' => 0,
            'postedAt' => '2026-09-07T10:00:00+00:00',
            'routineExecutionMemo' => '振り返り',
            'supportCount' => 7,
        ]], $result->items());
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
