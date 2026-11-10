<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Infrastructure\Query\GetMyRoutineExecutionHistory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryInput;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionHistory\GetMyRoutineExecutionHistoryInterface;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetMyRoutineExecutionHistoryTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_query_filters_by_executor_and_half_open_interval_and_orders_results(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $routine = '33333333-3333-4333-8333-333333333333';
        foreach ([$account, $other] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => '実行者', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active']);
        }
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $account, 'routine_name' => '朝活', 'available' => true]);
        foreach ([
            ['44444444-4444-4444-8444-000000000001', $account, '2026-01-01 00:00:00'],
            ['44444444-4444-4444-8444-000000000002', $account, '2026-01-01 12:00:00'],
            ['44444444-4444-4444-8444-000000000003', $account, '2026-01-02 00:00:00'],
            ['44444444-4444-4444-8444-000000000004', $other, '2026-01-01 12:00:00'],
        ] as [$id, $executor, $date]) {
            DB::table('routine_executions')->insert(['routine_execution_identifier' => $id, 'routine_identifier' => $routine, 'executor_account_identifier' => $executor, 'executed_at' => $date]);
        }

        $query = $this->app->make(GetMyRoutineExecutionHistoryInterface::class);
        $result = $query->execute(new GetMyRoutineExecutionHistoryInput($account, '2026-01-01 00:00:00', '2026-01-02 00:00:00'));

        self::assertSame(2, $result->total());
        self::assertSame(['44444444-4444-4444-8444-000000000002', '44444444-4444-4444-8444-000000000001'], array_column($result->items(), 'routineExecutionIdentifier'));
        self::assertSame('2026-01-01T12:00:00.000000+00:00', $result->items()[0]['executedAt']);
        self::assertNull($result->items()[0]['postedAt']);
        self::assertSame(0, $query->execute(new GetMyRoutineExecutionHistoryInput($account, '2026-02-01 00:00:00', '2026-03-01 00:00:00'))->total());
    }
}
