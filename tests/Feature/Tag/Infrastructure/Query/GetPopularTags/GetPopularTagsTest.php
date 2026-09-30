<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Infrastructure\Query\GetPopularTags;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsInput;
use Src\Tag\Infrastructure\Query\GetPopularTags\GetPopularTags;
use Tests\TestCase;

final class GetPopularTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_available_tags_with_distinct_public_routine_counts_in_popularity_order(): void
    {
        $this->insertAccount('10000000-0000-4000-8000-000000000001');
        $this->insertTag('20000000-0000-4000-8000-000000000001', '朝活');
        $this->insertTag('20000000-0000-4000-8000-000000000002', '読書');
        $this->insertTag('20000000-0000-4000-8000-000000000003', '運動');
        $this->insertTag('20000000-0000-4000-8000-000000000004', '料理');
        $this->insertTag('20000000-0000-4000-8000-000000000005', '読書');

        $this->insertRoutine('30000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000004', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000002');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000003');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000002');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000005');
        $this->insertRoutineExecution('40000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001');
        $this->insertRoutineExecution('40000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001');
        $this->insertPost('50000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001');
        $this->insertPost('50000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000001');

        $result = (new GetPopularTags)->execute(new GetPopularTagsInput);

        self::assertSame([
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000001', 'tagName' => '朝活', 'routineCount' => 3],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000002', 'tagName' => '読書', 'routineCount' => 2],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000005', 'tagName' => '読書', 'routineCount' => 1],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000003', 'tagName' => '運動', 'routineCount' => 1],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000004', 'tagName' => '料理', 'routineCount' => 0],
        ], $result->tags());
    }

    public function test_excludes_unavailable_tags_and_ineligible_routine_links_routines_and_owners(): void
    {
        $this->insertAccount('10000000-0000-4000-8000-000000000001');
        $this->insertAccount('10000000-0000-4000-8000-000000000002', false);
        $this->insertAccount('10000000-0000-4000-8000-000000000003', true, 'banned');
        $this->insertAccount('10000000-0000-4000-8000-000000000004', true, 'active', 'private');
        $this->insertTag('20000000-0000-4000-8000-000000000001', '利用不可タグ', false);
        $this->insertTag('20000000-0000-4000-8000-000000000002', '無効な紐付け');
        $this->insertTag('20000000-0000-4000-8000-000000000003', '無効なルーティン');
        $this->insertTag('20000000-0000-4000-8000-000000000004', '利用不可所有者');
        $this->insertTag('20000000-0000-4000-8000-000000000005', '停止済み所有者');
        $this->insertTag('20000000-0000-4000-8000-000000000006', '非公開所有者');

        $this->insertRoutine('30000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001', false);
        $this->insertRoutine('30000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000002');
        $this->insertRoutine('30000000-0000-4000-8000-000000000004', '10000000-0000-4000-8000-000000000003');
        $this->insertRoutine('30000000-0000-4000-8000-000000000005', '10000000-0000-4000-8000-000000000004');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000002', false);
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000003');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000004');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000005');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000005', '20000000-0000-4000-8000-000000000006');

        $result = (new GetPopularTags)->execute(new GetPopularTagsInput);

        self::assertSame([
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000005', 'tagName' => '停止済み所有者', 'routineCount' => 0],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000004', 'tagName' => '利用不可所有者', 'routineCount' => 0],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000003', 'tagName' => '無効なルーティン', 'routineCount' => 0],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000002', 'tagName' => '無効な紐付け', 'routineCount' => 0],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000006', 'tagName' => '非公開所有者', 'routineCount' => 0],
        ], $result->tags());
    }

    public function test_returns_an_empty_list_when_there_are_no_tags(): void
    {
        $result = (new GetPopularTags)->execute(new GetPopularTagsInput);

        self::assertSame([], $result->tags());
    }

    public function test_counts_multiple_tags_with_a_single_query(): void
    {
        $this->insertAccount('10000000-0000-4000-8000-000000000001');
        $this->insertTag('20000000-0000-4000-8000-000000000001', '朝活');
        $this->insertTag('20000000-0000-4000-8000-000000000002', '読書');
        $this->insertRoutine('30000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000002');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = (new GetPopularTags)->execute(new GetPopularTagsInput);

        self::assertSame([
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000001', 'tagName' => '朝活', 'routineCount' => 1],
            ['tagIdentifier' => '20000000-0000-4000-8000-000000000002', 'tagName' => '読書', 'routineCount' => 1],
        ], $result->tags());
        self::assertCount(1, DB::getQueryLog());
    }

    private function insertAccount(string $identifier, bool $available = true, string $status = 'active', string $visibility = 'public'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $identifier,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'visibility' => $visibility,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTag(string $identifier, string $name, bool $available = true): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier, bool $available = true): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $identifier,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutineTag(string $routineIdentifier, string $tagIdentifier, bool $available = true): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
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

    private function insertPost(string $identifier, string $routineIdentifier): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => 'routine',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
