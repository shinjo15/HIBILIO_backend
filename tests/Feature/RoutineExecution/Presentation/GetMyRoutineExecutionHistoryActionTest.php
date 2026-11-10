<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetMyRoutineExecutionHistoryActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_history_requires_authentication(): void
    {
        $this->getJson('/api/my/routine-executions/history?start=2026-01-01T00:00:00Z&end=2027-01-01T00:00:00Z')
            ->assertUnauthorized();
    }

    public function test_history_requires_both_dates(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/my/routine-executions/history')
            ->assertUnprocessable()->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_history_returns_all_own_records_with_offset_boundaries_and_no_pagination(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $routine = '33333333-3333-4333-8333-333333333333';
        foreach ([$account, $other] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => '実行者', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active']);
        }
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $account, 'routine_name' => '朝活', 'available' => true]);
        for ($i = 0; $i < 125; $i++) {
            DB::table('routine_executions')->insert(['routine_execution_identifier' => sprintf('44444444-4444-4444-8444-%012d', $i), 'routine_identifier' => $routine, 'executor_account_identifier' => $account, 'executed_at' => '2025-12-31 15:00:00']);
        }
        DB::table('routine_executions')->insert(['routine_execution_identifier' => '55555555-5555-4555-8555-555555555555', 'routine_identifier' => $routine, 'executor_account_identifier' => $account, 'executed_at' => '2026-01-01 15:00:00']);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $routine, 'executor_account_identifier' => $other, 'executed_at' => '2025-12-31 15:00:00']);
        $query = http_build_query(['start' => '2026-01-01T00:00:00+09:00', 'end' => '2026-01-02T00:00:00+09:00', 'page' => 2, 'number_of_items_per_page' => 1]);
        $response = $this->withSession(['account_identifier' => $account])->getJson('/api/my/routine-executions/history?'.$query)
            ->assertOk()->assertJsonPath('total', 125)->assertJsonCount(125, 'items')
            ->assertJsonPath('items.0.executed_at', '2025-12-31T15:00:00.000000+00:00');
        self::assertSame(array_fill(0, 125, $account), array_column($response->json('items'), 'account_identifier'));
    }

    public function test_history_rejects_invalid_ranges(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111']);
        foreach ([
            ['start' => '2026-01-01T00:00:00Z'],
            ['end' => '2026-01-01T00:00:00Z'],
            ['start' => '2026-01-01T00:00:00', 'end' => '2027-01-01T00:00:00Z'],
            ['start' => '2026-02-30T00:00:00Z', 'end' => '2027-01-01T00:00:00Z'],
            ['start' => '2026-01-01T00:00:00Z', 'end' => '2026-01-01T09:00:00+09:00'],
        ] as $query) {
            $this->getJson('/api/my/routine-executions/history?'.http_build_query($query))->assertUnprocessable();
        }
    }
}
