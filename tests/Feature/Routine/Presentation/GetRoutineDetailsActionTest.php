<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetRoutineDetailsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_anonymous_public_routine_details_with_exact_json(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        $this->insertAccount($accountIdentifier, true, 'active');
        $this->insertRoutine($routineIdentifier, $accountIdentifier, null, true, '朝の集中', '仕事前のルーティン', 45);
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $accountIdentifier, $routineIdentifier, true, '公開カスタム', null, null);
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $accountIdentifier, $routineIdentifier, false, '非公開カスタム', null, null);
        $this->insertRoutineExecution('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $accountIdentifier, $routineIdentifier);
        $this->insertRoutineExecution('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $accountIdentifier, $routineIdentifier);
        $this->insertRoutineExecution('ffffffff-ffff-4fff-8fff-ffffffffffff', $accountIdentifier, $routineIdentifier);
        $this->insertPost('12121212-1212-4121-8121-121212121212', $routineIdentifier, 'routine', true, 4);
        $this->insertPost('13131313-1313-4131-8131-131313131313', $routineIdentifier, 'routine', true, 6);
        $this->insertPost('14141414-1414-4141-8141-141414141414', $routineIdentifier, 'routine', false, 100);
        $this->insertPost('15151515-1515-4151-8151-151515151515', $routineIdentifier, 'action', true, 100);
        $this->insertRoutineAction('22222222-2222-4222-8222-222222222222', $routineIdentifier, true, '最初のAction', '最初のメモ', 10, '2026-09-07 08:00:00');
        $this->insertRoutineAction('33333333-3333-4333-8333-333333333333', $routineIdentifier, true, '同時刻で後のAction', null, null, '2026-09-07 08:00:00');
        $this->insertRoutineAction('44444444-4444-4444-8444-444444444444', $routineIdentifier, false, '非公開Action', '非公開メモ', 20, '2026-09-07 07:00:00');

        $this->getJson("/api/routines/{$routineIdentifier}")
            ->assertOk()
            ->assertExactJson([
                'account_identifier' => $accountIdentifier,
                'account_name' => '作成者',
                'routine_name' => '朝の集中',
                'routine_memo' => '仕事前のルーティン',
                'routine_execution_minutes' => 45,
                'execution_count' => 3,
                'customization_count' => 1,
                'like_count' => 10,
                'routine_actions' => [
                    [
                        'routine_action_identifier' => '22222222-2222-4222-8222-222222222222',
                        'action_name' => '最初のAction',
                        'action_memo' => '最初のメモ',
                        'action_minutes' => 10,
                    ],
                    [
                        'routine_action_identifier' => '33333333-3333-4333-8333-333333333333',
                        'action_name' => '同時刻で後のAction',
                        'action_memo' => null,
                        'action_minutes' => null,
                    ],
                ],
            ]);
    }

    public function test_returns_not_found_for_non_public_routines(): void
    {
        $activeAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $unavailableAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $temporarilyBannedAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $permanentlyBannedAccountIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->insertAccount($activeAccountIdentifier, true, 'active');
        $this->insertAccount($unavailableAccountIdentifier, false, 'active');
        $this->insertAccount($temporarilyBannedAccountIdentifier, true, 'temporarily_banned');
        $this->insertAccount($permanentlyBannedAccountIdentifier, true, 'permanently_banned');
        $this->insertRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $activeAccountIdentifier, null, false, '非公開Routine', null, null);
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $unavailableAccountIdentifier, null, true, '非公開作成者Routine', null, null);
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $temporarilyBannedAccountIdentifier, null, true, '一時停止中作成者Routine', null, null);
        $this->insertRoutine('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $permanentlyBannedAccountIdentifier, null, true, '永久停止中作成者Routine', null, null);

        $this->getJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->assertNotFound();
        $this->getJson('/api/routines/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->assertNotFound();
        $this->getJson('/api/routines/cccccccc-cccc-4ccc-8ccc-cccccccccccc')->assertNotFound();
        $this->getJson('/api/routines/dddddddd-dddd-4ddd-8ddd-dddddddddddd')->assertNotFound();
    }

    private function insertAccount(string $identifier, bool $available, string $status): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => '作成者',
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier, ?string $parentRoutineIdentifier, bool $available, string $name, ?string $memo, ?int $minutes): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'parent_routine_identifier' => $parentRoutineIdentifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $name,
            'routine_memo' => $memo,
            'routine_execution_minutes' => $minutes,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutineExecution(string $identifier, string $accountIdentifier, string $routineIdentifier): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'executor_account_identifier' => $accountIdentifier,
            'routine_identifier' => $routineIdentifier,
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPost(string $identifier, string $routineIdentifier, string $category, bool $available, int $likeCount): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => $category,
            'post_like_count' => $likeCount,
            'post_support_count' => 0,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutineAction(string $identifier, string $routineIdentifier, bool $available, string $name, ?string $memo, ?int $minutes, string $createdAt): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $identifier,
            'parent_routine_action_identifier' => null,
            'routine_identifier' => $routineIdentifier,
            'action_name' => $name,
            'action_memo' => $memo,
            'action_minutes' => $minutes,
            'available' => $available,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
