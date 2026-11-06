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
