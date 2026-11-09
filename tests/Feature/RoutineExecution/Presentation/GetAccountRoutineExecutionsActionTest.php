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
        $this->getJson('/api/my/routine-executions?'.http_build_query(['start' => '2030-01-01T00:00:00Z', 'end' => '2031-01-01T00:00:00Z']))
            ->assertOk()->assertExactJson($this->expectedResponse($accountIdentifier, $routineIdentifier, $routineExecutionIdentifier));
    }

    public function test_only_executor_can_see_execution_with_only_unavailable_posts(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';
        $executionIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($accountIdentifier, $routineIdentifier, $executionIdentifier);
        DB::table('posts')->update(['available' => false]);
        $expected = $this->expectedResponse($accountIdentifier, $routineIdentifier, $executionIdentifier);
        $expected['items'][0]['posted_at'] = null;
        $expected['items'][0]['support_count'] = null;
        $expected['items'][0]['supported'] = null;

        $this->getJson("/api/accounts/{$accountIdentifier}/routine-executions")->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        $this->withSession(['account_identifier' => $accountIdentifier])
            ->getJson("/api/accounts/{$accountIdentifier}/routine-executions")->assertOk()->assertExactJson($expected);
        $this->withSession(['account_identifier' => $accountIdentifier])
            ->getJson('/api/my/routine-executions')->assertOk()->assertExactJson($expected);
    }

    public function test_oldest_valid_post_is_selected_once_with_deterministic_ties(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';
        $executionIdentifier = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($accountIdentifier, $routineIdentifier, $executionIdentifier);
        foreach ([
            ['44444444-4444-4444-8444-444444444442', '2026-09-06 10:00:00', 2],
            ['44444444-4444-4444-8444-444444444441', '2026-09-06 10:00:00', 1],
            ['44444444-4444-4444-8444-444444444443', null, 3],
        ] as [$id, $createdAt, $count]) {
            DB::table('posts')->insert([
                'post_identifier' => $id, 'routine_identifier' => $routineIdentifier,
                'routine_execution_identifier' => $executionIdentifier, 'post_category' => 'action',
                'post_like_count' => 0, 'post_support_count' => $count, 'available' => true,
                'created_at' => $createdAt, 'updated_at' => $createdAt,
            ]);
        }
        $expected = $this->expectedResponse($accountIdentifier, $routineIdentifier, $executionIdentifier);
        $expected['items'][0]['posted_at'] = '2026-09-06T10:00:00+00:00';
        $expected['items'][0]['support_count'] = 1;
        $this->getJson("/api/accounts/{$accountIdentifier}/routine-executions")->assertOk()->assertExactJson($expected);
        $this->withSession(['account_identifier' => $accountIdentifier])->getJson('/api/my/routine-executions')
            ->assertOk()->assertExactJson($expected);
        $interval = http_build_query(['start' => '2026-09-07T00:00:00Z', 'end' => '2026-09-08T00:00:00Z']);
        $this->withSession(['account_identifier' => $accountIdentifier])->getJson('/api/my/routine-executions/history?'.$interval)
            ->assertOk()->assertExactJson(array_replace_recursive($expected, ['items' => [['executed_at' => '2026-09-07T10:00:00.000000+00:00']]]));
    }

    public function test_other_viewer_visibility_applies_even_when_there_is_no_post(): void
    {
        $executor = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        $viewer = '55555555-5555-4555-8555-555555555555';
        $author = '66666666-6666-4666-8666-666666666666';
        $this->createExecutionHistory($executor, $routine, $execution);
        DB::table('posts')->delete();
        foreach ([$viewer, $author] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => '他者', 'email_address' => "$id@example.com", 'available' => true, 'status' => 'active']);
        }
        DB::table('routines')->where('routine_identifier', $routine)->update(['account_identifier' => $author]);
        DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
        $url = "/api/accounts/{$executor}/routine-executions";
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $author]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        DB::table('accounts')->where('account_identifier', $executor)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $executor]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        DB::table('blocks')->insert(['blocking_account_identifier' => $author, 'blocked_account_identifier' => $viewer]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('blocks')->insert(['blocking_account_identifier' => $executor, 'blocked_account_identifier' => $viewer]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
    }

    public function test_pagination_is_stable_and_query_count_does_not_grow_with_executions(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($account, $routine, $execution);
        DB::table('posts')->delete();
        $url = "/api/accounts/{$account}/routine-executions";
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson($url)->assertOk()->assertJsonPath('total', 1);
        $singleCount = count(DB::getQueryLog());
        for ($i = 1; $i <= 5; $i++) {
            DB::table('routine_executions')->insert([
                'routine_execution_identifier' => sprintf('33333333-3333-4333-8333-%012d', $i),
                'routine_identifier' => $routine, 'executor_account_identifier' => $account,
                'executed_at' => '2026-09-07 10:00:00',
            ]);
        }
        DB::flushQueryLog();
        $this->getJson($url)->assertOk()->assertJsonPath('total', 6);
        self::assertSame($singleCount, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $ids = [];
        for ($page = 1; $page <= 6; $page++) {
            $response = $this->getJson("$url?page=$page&number_of_items_per_page=1")->assertOk()->assertJsonPath('total', 6);
            $ids[] = $response->json('items.0.routine_execution_identifier');
        }
        $sorted = $ids;
        sort($sorted);
        self::assertSame($sorted, $ids);
        self::assertCount(6, array_unique($ids));
    }

    public function test_interval_normalizes_offsets_includes_start_excludes_end_and_keeps_unposted_executions(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        $this->createExecutionHistory($account, $routine, $execution);
        DB::table('posts')->delete();
        DB::table('routine_executions')->where('routine_execution_identifier', $execution)->update(['executed_at' => '2025-12-31 15:00:00']);
        // PostgreSQL's existing timestamp(0) rounds fractional seconds; SQLite can retain them.
        foreach (['2025-12-31 14:59:59', '2025-12-31 15:00:01', '2026-12-31 15:00:00'] as $index => $date) {
            DB::table('routine_executions')->insert(['routine_execution_identifier' => sprintf('33333333-3333-4333-8333-%012d', $index + 1), 'routine_identifier' => $routine, 'executor_account_identifier' => $account, 'executed_at' => $date]);
        }
        $query = http_build_query(['start' => '2026-01-01T00:00:00+09:00', 'end' => '2027-01-01T00:00:00+09:00']);
        $response = $this->withSession(['account_identifier' => $account])->getJson('/api/my/routine-executions/history?'.$query)->assertOk()->assertJsonPath('total', 2);
        $response->assertJsonPath('items.0.routine_execution_identifier', '33333333-3333-4333-8333-000000000002')->assertJsonPath('items.0.executed_at', '2025-12-31T15:00:01.000000+00:00')->assertJsonPath('items.1.routine_execution_identifier', $execution)->assertJsonPath('items.1.posted_at', null);
        $fractional = http_build_query(['start' => '2025-12-31T10:00:00.123456-05:00', 'end' => '2025-12-31T15:00:01.000001Z']);
        $this->getJson('/api/my/routine-executions/history?'.$fractional)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.routine_execution_identifier', '33333333-3333-4333-8333-000000000002')->assertJsonPath('items.0.executed_at', '2025-12-31T15:00:01.000000+00:00');
        $fractionalExclusion = http_build_query(['start' => '2025-12-31T15:00:00.000001Z', 'end' => '2025-12-31T15:00:01Z']);
        $this->getJson('/api/my/routine-executions/history?'.$fractionalExclusion)->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        $adjacent = http_build_query(['start' => '2027-01-01T00:00:00+09:00', 'end' => '2028-01-01T00:00:00+09:00']);
        $this->getJson('/api/my/routine-executions/history?'.$adjacent)->assertOk()->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.routine_execution_identifier', '33333333-3333-4333-8333-000000000003');
    }

    public function test_interval_validation_and_authentication(): void
    {
        $this->getJson('/api/my/routine-executions')->assertUnauthorized();
        $this->createExecutionHistory('11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222', '33333333-3333-4333-8333-333333333333');
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111']);
        foreach ([
            ['start' => '2026-01-01T00:00:00Z'],
            ['end' => '2026-01-01T00:00:00Z'],
            ['start' => '', 'end' => ''],
            ['start' => '2026-01-01T00:00:00', 'end' => '2027-01-01T00:00:00Z'],
            ['start' => '2026-02-30T00:00:00Z', 'end' => '2027-01-01T00:00:00Z'],
            ['start' => '2026-01-02T00:00:00Z', 'end' => '2026-01-01T00:00:00Z'],
            ['start' => '2026-01-01T00:00:00Z', 'end' => '2026-01-01T09:00:00+09:00'],
        ] as $parameters) {
            $this->getJson('/api/my/routine-executions/history?'.http_build_query($parameters))->assertUnprocessable();
        }
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->getJson('/api/my/routine-executions/history?'.http_build_query(['start' => '2026-01-01T00:00:00.000001Z', 'end' => '2026-01-01T00:00:00.000002Z']))->assertOk();
        $this->getJson('/api/my/routine-executions?page=0&number_of_items_per_page=121')->assertUnprocessable()->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    public function test_interval_rejects_dates_outside_the_utc_database_range_after_offset_conversion(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $this->createExecutionHistory($account, '22222222-2222-4222-8222-222222222222', '33333333-3333-4333-8333-333333333333');
        $this->withSession(['account_identifier' => $account]);

        foreach ([
            [['start' => '0001-01-01T00:00:00+00:01', 'end' => '0001-01-02T00:00:00Z'], 'start'],
            [['start' => '0001-01-01T00:00:00+00:02', 'end' => '0001-01-01T00:00:00+00:01'], 'end'],
            [['start' => '9999-12-31T23:59:59-00:01', 'end' => '9999-12-31T23:59:59-00:02'], 'start'],
            [['start' => '9999-12-30T00:00:00Z', 'end' => '9999-12-31T23:59:59-00:01'], 'end'],
        ] as [$parameters, $field]) {
            $this->getJson('/api/my/routine-executions/history?'.http_build_query($parameters))
                ->assertUnprocessable()->assertJsonValidationErrors([$field]);
        }

        $this->getJson('/api/my/routine-executions/history?'.http_build_query([
            'start' => '0001-01-01T00:01:00+00:01',
            'end' => '9999-12-31T23:58:59.999999-00:01',
        ]))->assertOk()->assertJsonPath('total', 1);
    }

    public function test_interval_returns_all_matching_executions_regardless_of_pagination(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        $other = '55555555-5555-4555-8555-555555555555';
        $this->createExecutionHistory($account, $routine, $execution);
        DB::table('posts')->delete();
        DB::table('accounts')->insert(['account_identifier' => $other, 'account_name' => 'other', 'email_address' => 'other@example.com', 'available' => true, 'status' => 'active']);
        for ($i = 0; $i < 125; $i++) {
            DB::table('routine_executions')->insert(['routine_execution_identifier' => sprintf('33333333-3333-4333-8333-%012d', $i), 'routine_identifier' => $routine, 'executor_account_identifier' => $account, 'executed_at' => '2026-09-07 10:00:00']);
        }
        DB::table('routine_executions')->insert(['routine_execution_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $routine, 'executor_account_identifier' => $other, 'executed_at' => '2026-09-07 10:00:00']);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => '77777777-7777-4777-8777-777777777777', 'routine_identifier' => $routine, 'executor_account_identifier' => $account, 'executed_at' => '2026-09-08 00:00:00']);
        $expected = [];
        for ($i = 0; $i < 125; $i++) {
            $expected[] = sprintf('33333333-3333-4333-8333-%012d', $i);
        }
        $expected[] = $execution;
        foreach ([[], ['page' => 2, 'number_of_items_per_page' => 50], ['page' => 'invalid', 'number_of_items_per_page' => 121]] as $pagination) {
            $query = http_build_query(array_merge(['start' => '2026-09-07T00:00:00Z', 'end' => '2026-09-08T00:00:00Z'], $pagination));
            $response = $this->withSession(['account_identifier' => $account])->getJson('/api/my/routine-executions/history?'.$query)->assertOk()->assertJsonPath('total', 126);
            self::assertSame($expected, array_column($response->json('items'), 'routine_execution_identifier'));
        }
        $this->getJson('/api/my/routine-executions?page=1&number_of_items_per_page=1')->assertOk()->assertJsonPath('total', 127)->assertJsonCount(1, 'items')->assertJsonPath('items.0.routine_execution_identifier', '77777777-7777-4777-8777-777777777777');
        $this->getJson('/api/my/routine-executions?page=2&number_of_items_per_page=1')->assertOk()->assertJsonPath('total', 127)->assertJsonCount(1, 'items')->assertJsonPath('items.0.routine_execution_identifier', $expected[0]);
    }

    public function test_interval_preserves_routine_author_visibility_and_block_rules(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        $author = '66666666-6666-4666-8666-666666666666';
        $this->createExecutionHistory($account, $routine, $execution);
        DB::table('accounts')->insert(['account_identifier' => $author, 'account_name' => 'author', 'email_address' => 'author@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'private']);
        DB::table('routines')->where('routine_identifier', $routine)->update(['account_identifier' => $author]);
        $url = '/api/my/routine-executions/history?'.http_build_query(['start' => '2026-01-01T00:00:00Z', 'end' => '2027-01-01T00:00:00Z']);
        $this->withSession(['account_identifier' => $account])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $account, 'followed_account_identifier' => $author]);
        $this->withSession(['account_identifier' => $account])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        DB::table('blocks')->insert(['blocking_account_identifier' => $author, 'blocked_account_identifier' => $account]);
        $this->withSession(['account_identifier' => $account])->getJson($url)->assertOk()->assertJsonPath('total', 0);
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
