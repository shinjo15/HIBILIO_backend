<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetCustomizedRoutinesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_public_customized_routines_with_exact_fields_in_requested_page_and_total(): void
    {
        $parentAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $parentRoutineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $firstChildAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $secondChildAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $thirdChildAccountIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->insertAccount($parentAccountIdentifier, '親作成者');
        $this->insertAccount($firstChildAccountIdentifier, '一番目作成者');
        $this->insertAccount($secondChildAccountIdentifier, '二番目作成者');
        $this->insertAccount($thirdChildAccountIdentifier, '三番目作成者');
        $this->insertAccount('55555555-5555-4555-8555-555555555555', '停止中作成者', true, 'temporarily_banned');
        $this->insertRoutine($parentRoutineIdentifier, $parentAccountIdentifier, null, true, '親Routine', null, null, '2026-09-07 09:00:00');
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $firstChildAccountIdentifier, $parentRoutineIdentifier, true, '同時刻で識別子が後', '一番目のメモ', 30, '2026-09-07 11:00:00');
        $this->insertRoutine('99999999-9999-4999-8999-999999999999', $secondChildAccountIdentifier, $parentRoutineIdentifier, true, '同時刻で識別子が先', null, null, '2026-09-07 11:00:00');
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $thirdChildAccountIdentifier, $parentRoutineIdentifier, true, '古いRoutine', null, 15, '2026-09-07 10:00:00');
        $this->insertRoutine('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $firstChildAccountIdentifier, $parentRoutineIdentifier, false, '非公開Routine', null, null, '2026-09-07 12:00:00');
        $this->insertRoutine('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '55555555-5555-4555-8555-555555555555', $parentRoutineIdentifier, true, '停止中作成者Routine', null, null, '2026-09-07 12:00:00');

        $this->insertRoutineExecution('12121212-1212-4121-8121-121212121212', $firstChildAccountIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->insertRoutineExecution('13131313-1313-4131-8131-131313131313', $firstChildAccountIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->insertRoutine('14141414-1414-4141-8141-141414141414', $firstChildAccountIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', true, '孫Routine', null, null, '2026-09-07 12:00:00');
        $this->insertRoutine('15151515-1515-4151-8151-151515151515', $firstChildAccountIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', false, '非公開孫Routine', null, null, '2026-09-07 12:00:00');
        $this->insertPost('16161616-1616-4161-8161-161616161616', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', true, 4);
        $this->insertPost('17171717-1717-4171-8171-171717171717', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', true, 6);
        $this->insertPost('18181818-1818-4181-8181-181818181818', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'routine', false, 100);
        $this->insertPost('19191919-1919-4191-8191-191919191919', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'action', true, 100);

        $this->getJson("/api/routines/{$parentRoutineIdentifier}/customized?page=1&number_of_items_per_page=2")
            ->assertOk()
            ->assertExactJson([
                'items' => [
                    [
                        'routine_identifier' => '99999999-9999-4999-8999-999999999999',
                        'account_identifier' => $secondChildAccountIdentifier,
                        'account_name' => '二番目作成者',
                        'routine_name' => '同時刻で識別子が先',
                        'routine_memo' => null,
                        'routine_execution_minutes' => null,
                        'execution_count' => 0,
                        'customization_count' => 0,
                        'like_count' => 0,
                    ],
                    [
                        'routine_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                        'account_identifier' => $firstChildAccountIdentifier,
                        'account_name' => '一番目作成者',
                        'routine_name' => '同時刻で識別子が後',
                        'routine_memo' => '一番目のメモ',
                        'routine_execution_minutes' => 30,
                        'execution_count' => 2,
                        'customization_count' => 1,
                        'like_count' => 10,
                    ],
                ],
                'total' => 3,
            ]);
    }

    public function test_returns_not_found_when_the_parent_routine_is_missing_or_not_public(): void
    {
        $activeAccountIdentifier = '11111111-1111-4111-8111-111111111111';
        $unavailableAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $bannedAccountIdentifier = '33333333-3333-4333-8333-333333333333';

        $this->insertAccount($activeAccountIdentifier, '公開作成者');
        $this->insertAccount($unavailableAccountIdentifier, '非公開作成者', false);
        $this->insertAccount($bannedAccountIdentifier, '停止中作成者', true, 'permanently_banned');
        $this->insertRoutine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $activeAccountIdentifier, null, false, '非公開親', null, null, '2026-09-07 09:00:00');
        $this->insertRoutine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $unavailableAccountIdentifier, null, true, '非公開作成者親', null, null, '2026-09-07 09:00:00');
        $this->insertRoutine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $bannedAccountIdentifier, null, true, '停止中作成者親', null, null, '2026-09-07 09:00:00');

        $this->getJson('/api/routines/99999999-9999-4999-8999-999999999999/customized')->assertNotFound();
        $this->getJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/customized')->assertNotFound();
        $this->getJson('/api/routines/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb/customized')->assertNotFound();
        $this->getJson('/api/routines/cccccccc-cccc-4ccc-8ccc-cccccccccccc/customized')->assertNotFound();
    }

    public function test_returns_validation_errors_for_invalid_pagination_parameters(): void
    {
        $this->getJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/customized?page=0&number_of_items_per_page=zero')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    private function insertAccount(string $identifier, string $name, bool $available = true, string $status = 'active'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier, ?string $parentRoutineIdentifier, bool $available, string $name, ?string $memo, ?int $minutes, string $createdAt): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'parent_routine_identifier' => $parentRoutineIdentifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $name,
            'routine_memo' => $memo,
            'routine_execution_minutes' => $minutes,
            'available' => $available,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function insertRoutineExecution(string $identifier, string $accountIdentifier, string $routineIdentifier): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'executor_account_identifier' => $accountIdentifier,
            'routine_identifier' => $routineIdentifier,
            'executed_at' => '2026-09-07 12:00:00',
            'created_at' => '2026-09-07 12:00:00',
            'updated_at' => '2026-09-07 12:00:00',
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
            'created_at' => '2026-09-07 12:00:00',
            'updated_at' => '2026-09-07 12:00:00',
        ]);
    }
}
