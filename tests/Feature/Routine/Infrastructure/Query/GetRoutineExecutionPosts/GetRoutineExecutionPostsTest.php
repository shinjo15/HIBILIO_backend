<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Infrastructure\Query\GetRoutineExecutionPosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInput;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;
use Src\Routine\Infrastructure\Query\GetRoutineExecutionPosts\GetRoutineExecutionPosts;
use Tests\TestCase;

final class GetRoutineExecutionPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_action_post_fields_and_executed_action_count(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineExecutionIdentifier = '33333333-3333-4333-8333-333333333333';

        $this->createAccount($executorAccountIdentifier, '実行者');
        $this->createRoutine($routineIdentifier);
        $this->createRoutineExecution($routineExecutionIdentifier, $executorAccountIdentifier, $routineIdentifier, '振り返りメモ');
        $this->createRoutineAction('44444444-4444-4444-8444-444444444444', $routineIdentifier);
        $this->createRoutineAction('55555555-5555-4555-8555-555555555555', $routineIdentifier);
        $this->createRoutineExecutionAction($routineExecutionIdentifier, '44444444-4444-4444-8444-444444444444');
        $this->createRoutineExecutionAction($routineExecutionIdentifier, '55555555-5555-4555-8555-555555555555');
        $this->createPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $routineExecutionIdentifier, 'action', true, 7, '2026-09-07 10:00:00');

        $result = (new GetRoutineExecutionPosts)->execute(new GetRoutineExecutionPostsInput($routineIdentifier, 1, 20));

        self::assertSame([[
            'accountIdentifier' => $executorAccountIdentifier,
            'accountName' => '実行者',
            'executedActionCount' => 2,
            'postedAt' => '2026-09-07T10:00:00+00:00',
            'routineExecutionMemo' => '振り返りメモ',
            'supportCount' => 7,
        ]], $result->items());
    }

    public function test_it_returns_only_public_action_posts_for_the_specified_routine(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';
        $publicExecutorAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $unavailableExecutorAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $bannedExecutorAccountIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->createAccount($publicExecutorAccountIdentifier, '公開実行者');
        $this->createAccount($unavailableExecutorAccountIdentifier, '非公開実行者', false);
        $this->createAccount($bannedExecutorAccountIdentifier, '停止中実行者', true, 'temporarily_banned');
        $this->createRoutine($routineIdentifier);
        $this->createRoutine('55555555-5555-4555-8555-555555555555');
        $this->createRoutineExecution('66666666-6666-4666-8666-666666666666', $publicExecutorAccountIdentifier, $routineIdentifier, null);
        $this->createRoutineExecution('77777777-7777-4777-8777-777777777777', $unavailableExecutorAccountIdentifier, $routineIdentifier, null);
        $this->createRoutineExecution('88888888-8888-4888-8888-888888888888', $bannedExecutorAccountIdentifier, $routineIdentifier, null);
        $this->createRoutineExecution('99999999-9999-4999-8999-999999999999', $publicExecutorAccountIdentifier, '55555555-5555-4555-8555-555555555555', null);
        $this->createRoutineExecution('10101010-1010-4010-8010-101010101010', $publicExecutorAccountIdentifier, '55555555-5555-4555-8555-555555555555', null);
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $routineIdentifier, '66666666-6666-4666-8666-666666666666', 'action', true, 0, '2026-09-07 10:00:00');
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $routineIdentifier, '66666666-6666-4666-8666-666666666666', 'routine', true, 0, '2026-09-07 10:00:00');
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $routineIdentifier, null, 'action', true, 0, '2026-09-07 10:00:00');
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $routineIdentifier, '66666666-6666-4666-8666-666666666666', 'action', false, 0, '2026-09-07 10:00:00');
        $this->createPost('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $routineIdentifier, '77777777-7777-4777-8777-777777777777', 'action', true, 0, '2026-09-07 10:00:00');
        $this->createPost('ffffffff-ffff-4fff-8fff-ffffffffffff', $routineIdentifier, '88888888-8888-4888-8888-888888888888', 'action', true, 0, '2026-09-07 10:00:00');
        $this->createPost('12121212-1212-4121-8121-121212121212', '55555555-5555-4555-8555-555555555555', '99999999-9999-4999-8999-999999999999', 'action', true, 0, '2026-09-07 10:00:00');
        $this->createPost('13131313-1313-4131-8131-131313131313', $routineIdentifier, '10101010-1010-4010-8010-101010101010', 'action', true, 0, '2026-09-07 10:00:00');

        $result = (new GetRoutineExecutionPosts)->execute(new GetRoutineExecutionPostsInput($routineIdentifier, 1, 20));

        self::assertSame(['22222222-2222-4222-8222-222222222222'], array_column($result->items(), 'accountIdentifier'));
    }

    public function test_it_returns_zero_action_counts_in_posted_at_descending_and_post_identifier_ascending_order(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';

        $this->createAccount('22222222-2222-4222-8222-222222222222', '二番目');
        $this->createAccount('33333333-3333-4333-8333-333333333333', '一番目');
        $this->createAccount('44444444-4444-4444-8444-444444444444', '三番目');
        $this->createRoutine($routineIdentifier);
        $this->createRoutineExecution('55555555-5555-4555-8555-555555555555', '22222222-2222-4222-8222-222222222222', $routineIdentifier, null);
        $this->createRoutineExecution('66666666-6666-4666-8666-666666666666', '33333333-3333-4333-8333-333333333333', $routineIdentifier, null);
        $this->createRoutineExecution('77777777-7777-4777-8777-777777777777', '44444444-4444-4444-8444-444444444444', $routineIdentifier, null);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $routineIdentifier, '55555555-5555-4555-8555-555555555555', 'action', true, 0, '2026-09-07 11:00:00');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $routineIdentifier, '66666666-6666-4666-8666-666666666666', 'action', true, 0, '2026-09-07 11:00:00');
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $routineIdentifier, '77777777-7777-4777-8777-777777777777', 'action', true, 0, '2026-09-07 10:00:00');

        $result = (new GetRoutineExecutionPosts)->execute(new GetRoutineExecutionPostsInput($routineIdentifier, 1, 20));

        self::assertSame([
            '33333333-3333-4333-8333-333333333333',
            '22222222-2222-4222-8222-222222222222',
            '44444444-4444-4444-8444-444444444444',
        ], array_column($result->items(), 'accountIdentifier'));
        self::assertSame([0, 0, 0], array_column($result->items(), 'executedActionCount'));
    }

    public function test_it_returns_the_second_page_item_and_total(): void
    {
        $routineIdentifier = '11111111-1111-4111-8111-111111111111';

        $this->createAccount('22222222-2222-4222-8222-222222222222', '一番目');
        $this->createAccount('33333333-3333-4333-8333-333333333333', '二番目');
        $this->createAccount('44444444-4444-4444-8444-444444444444', '三番目');
        $this->createRoutine($routineIdentifier);
        $this->createRoutineExecution('55555555-5555-4555-8555-555555555555', '22222222-2222-4222-8222-222222222222', $routineIdentifier, null);
        $this->createRoutineExecution('66666666-6666-4666-8666-666666666666', '33333333-3333-4333-8333-333333333333', $routineIdentifier, null);
        $this->createRoutineExecution('77777777-7777-4777-8777-777777777777', '44444444-4444-4444-8444-444444444444', $routineIdentifier, null);
        $this->createPost('88888888-8888-4888-8888-888888888888', $routineIdentifier, '55555555-5555-4555-8555-555555555555', 'action', true, 0, '2026-09-07 12:00:00');
        $this->createPost('99999999-9999-4999-8999-999999999999', $routineIdentifier, '66666666-6666-4666-8666-666666666666', 'action', true, 0, '2026-09-07 11:00:00');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $routineIdentifier, '77777777-7777-4777-8777-777777777777', 'action', true, 0, '2026-09-07 10:00:00');

        $result = (new GetRoutineExecutionPosts)->execute(new GetRoutineExecutionPostsInput($routineIdentifier, 2, 1));

        self::assertSame(['33333333-3333-4333-8333-333333333333'], array_column($result->items(), 'accountIdentifier'));
        self::assertSame(3, $result->total());
    }

    public function test_query_interface_is_bound_to_infrastructure_query(): void
    {
        self::assertInstanceOf(GetRoutineExecutionPosts::class, $this->app->make(GetRoutineExecutionPostsInterface::class));
    }

    private function createAccount(string $accountIdentifier, string $accountName, bool $available = true, string $status = 'active'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => $accountName,
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function createRoutine(string $routineIdentifier, bool $available = true): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $routineIdentifier,
            'routine_name' => '朝活',
            'account_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'available' => $available,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function createRoutineExecution(string $routineExecutionIdentifier, string $executorAccountIdentifier, string $routineIdentifier, ?string $memo): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'executor_account_identifier' => $executorAccountIdentifier,
            'routine_identifier' => $routineIdentifier,
            'executed_at' => '2026-09-07 09:00:00',
            'routine_execution_memo' => $memo,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function createRoutineAction(string $routineActionIdentifier, string $routineIdentifier): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $routineActionIdentifier,
            'routine_identifier' => $routineIdentifier,
            'action_name' => 'Action',
            'available' => true,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function createRoutineExecutionAction(string $routineExecutionIdentifier, string $routineActionIdentifier): void
    {
        DB::table('routine_execution_actions')->insert([
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'routine_action_identifier' => $routineActionIdentifier,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function createPost(string $postIdentifier, string $routineIdentifier, ?string $routineExecutionIdentifier, string $postCategory, bool $available, int $supportCount, string $createdAt): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $postIdentifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => $postCategory,
            'post_like_count' => 0,
            'post_support_count' => $supportCount,
            'available' => $available,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
