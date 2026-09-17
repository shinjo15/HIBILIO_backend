<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetRoutineExecutionDetailsActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_anonymous_public_routine_execution_details_with_executed_actions_and_tags(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = '33333333-3333-4333-8333-333333333333';
        $executionIdentifier = '44444444-4444-4444-8444-444444444444';
        $firstActionIdentifier = '55555555-5555-4555-8555-555555555555';
        $secondActionIdentifier = '66666666-6666-4666-8666-666666666666';
        $tagIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->routine($routineIdentifier, $authorIdentifier, '朝の集中', '仕事前のルーティン');
        $this->routineAction($firstActionIdentifier, $routineIdentifier, '準備', '机を整える', 5, '2026-09-01 08:00:00');
        $this->routineAction($secondActionIdentifier, $routineIdentifier, '作業', null, 25, '2026-09-01 08:01:00');
        $this->tag($tagIdentifier, '朝活');
        $this->routineTag($routineIdentifier, $tagIdentifier);
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-02 09:00:00', '集中できた');
        $this->routineExecutionAction($executionIdentifier, $firstActionIdentifier);
        $this->routineExecutionAction($executionIdentifier, $secondActionIdentifier);
        $this->executionPost('77777777-7777-4777-8777-777777777777', $routineIdentifier, $executionIdentifier, '2026-09-02 10:00:00', 3);

        $this->getJson("/api/routine-executions/{$executionIdentifier}")
            ->assertOk()
            ->assertExactJson([
                'routine_execution_identifier' => $executionIdentifier,
                'routine_identifier' => $routineIdentifier,
                'routine_name' => '朝の集中',
                'routine_memo' => '仕事前のルーティン',
                'account_identifier' => $executorIdentifier,
                'account_name' => '実行者',
                'icon_image_url' => "https://images.example/accounts/{$executorIdentifier}/icon",
                'routine_execution_memo' => '集中できた',
                'executed_at' => '2026-09-02T09:00:00+00:00',
                'posted_at' => '2026-09-02T10:00:00+00:00',
                'support_count' => 3,
                'supported' => false,
                'tags' => [[
                    'tag_identifier' => $tagIdentifier,
                    'tag_name' => '朝活',
                ]],
                'routine_execution_actions' => [
                    [
                        'routine_action_identifier' => $firstActionIdentifier,
                        'action_name' => '準備',
                        'action_memo' => '机を整える',
                        'action_minutes' => 5,
                    ],
                    [
                        'routine_action_identifier' => $secondActionIdentifier,
                        'action_name' => '作業',
                        'action_memo' => null,
                        'action_minutes' => 25,
                    ],
                ],
            ]);
    }

    public function test_returns_not_found_for_unknown_or_non_public_routine_execution_posts(): void
    {
        $activeAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $inactiveAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $unavailablePostExecutionIdentifier = '33333333-3333-4333-8333-333333333333';
        $unavailableRoutineExecutionIdentifier = '44444444-4444-4444-8444-444444444444';
        $inactiveExecutorExecutionIdentifier = '55555555-5555-4555-8555-555555555555';

        $this->account($activeAccountIdentifier, '公開アカウント');
        $this->account($inactiveAccountIdentifier, '停止中アカウント', 'temporarily_banned');
        $this->routine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $activeAccountIdentifier, '非公開投稿Routine', null);
        $this->routine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $activeAccountIdentifier, '非公開Routine', null, false);
        $this->routine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $activeAccountIdentifier, '停止中実行者Routine', null);
        $this->routineExecution($unavailablePostExecutionIdentifier, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $activeAccountIdentifier, '2026-09-02 09:00:00', null);
        $this->routineExecution($unavailableRoutineExecutionIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $activeAccountIdentifier, '2026-09-02 09:00:00', null);
        $this->routineExecution($inactiveExecutorExecutionIdentifier, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', $inactiveAccountIdentifier, '2026-09-02 09:00:00', null);
        $this->executionPost('66666666-6666-4666-8666-666666666661', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $unavailablePostExecutionIdentifier, '2026-09-02 10:00:00', 0, false);
        $this->executionPost('66666666-6666-4666-8666-666666666662', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $unavailableRoutineExecutionIdentifier, '2026-09-02 10:00:00', 0);
        $this->executionPost('66666666-6666-4666-8666-666666666663', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', $inactiveExecutorExecutionIdentifier, '2026-09-02 10:00:00', 0);

        $this->getJson('/api/routine-executions/99999999-9999-4999-8999-999999999999')->assertNotFound();
        $this->getJson("/api/routine-executions/{$unavailablePostExecutionIdentifier}")->assertNotFound();
        $this->getJson("/api/routine-executions/{$unavailableRoutineExecutionIdentifier}")->assertNotFound();
        $this->getJson("/api/routine-executions/{$inactiveExecutorExecutionIdentifier}")->assertNotFound();
    }

    public function test_returns_not_found_to_a_viewer_in_a_block_relationship_with_the_executor_or_routine_author(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $executorIdentifier = '33333333-3333-4333-8333-333333333333';
        $authorBlockedExecutionIdentifier = '44444444-4444-4444-8444-444444444444';
        $executorBlockedExecutionIdentifier = '55555555-5555-4555-8555-555555555555';

        $this->account($viewerIdentifier, '閲覧者');
        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->routine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $authorIdentifier, '作成者ブロックRoutine', null);
        $this->routine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $authorIdentifier, '実行者ブロックRoutine', null);
        $this->routineExecution($authorBlockedExecutionIdentifier, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $executorIdentifier, '2026-09-02 09:00:00', null);
        $this->routineExecution($executorBlockedExecutionIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $executorIdentifier, '2026-09-02 09:00:00', null);
        $this->executionPost('66666666-6666-4666-8666-666666666661', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $authorBlockedExecutionIdentifier, '2026-09-02 10:00:00', 0);
        $this->executionPost('66666666-6666-4666-8666-666666666662', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $executorBlockedExecutionIdentifier, '2026-09-02 10:00:00', 0);
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $viewerIdentifier,
            'blocked_account_identifier' => $authorIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $executorIdentifier,
            'blocked_account_identifier' => $viewerIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/routine-executions/{$authorBlockedExecutionIdentifier}")
            ->assertNotFound();
        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/routine-executions/{$executorBlockedExecutionIdentifier}")
            ->assertNotFound();
    }

    private function account(string $identifier, string $name, string $status = 'active'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => true,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routine(string $identifier, string $accountIdentifier, string $name, ?string $memo, bool $available = true): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $name,
            'routine_memo' => $memo,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routineAction(string $identifier, string $routineIdentifier, string $name, ?string $memo, ?int $minutes, string $createdAt): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'action_name' => $name,
            'action_memo' => $memo,
            'action_minutes' => $minutes,
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function tag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routineExecution(string $identifier, string $routineIdentifier, string $executorIdentifier, string $executedAt, ?string $memo): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'executor_account_identifier' => $executorIdentifier,
            'executed_at' => $executedAt,
            'routine_execution_memo' => $memo,
            'created_at' => $executedAt,
            'updated_at' => $executedAt,
        ]);
    }

    private function routineExecutionAction(string $routineExecutionIdentifier, string $routineActionIdentifier): void
    {
        DB::table('routine_execution_actions')->insert([
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'routine_action_identifier' => $routineActionIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function executionPost(string $identifier, string $routineIdentifier, string $routineExecutionIdentifier, string $createdAt, int $supportCount, bool $available = true): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => 'action',
            'post_support_count' => $supportCount,
            'available' => $available,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
