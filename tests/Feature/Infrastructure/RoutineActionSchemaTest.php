<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class RoutineActionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrates_the_routine_action_primary_key_and_self_referencing_foreign_key(): void
    {
        $primaryKey = collect(Schema::getIndexes('routine_actions'))->firstWhere('primary', true);

        self::assertNotNull($primaryKey);
        self::assertSame(['routine_action_identifier'], $primaryKey['columns']);
        $foreignKeys = collect(Schema::getForeignKeys('routine_actions'));
        $parentKey = $foreignKeys->firstWhere('columns', ['parent_routine_action_identifier']);

        self::assertNotNull($parentKey);
        self::assertSame('routine_actions', $parentKey['foreign_table']);
        self::assertSame(['routine_action_identifier'], $parentKey['foreign_columns']);
        self::assertSame('set null', strtolower($parentKey['on_delete']));
    }

    public function test_deleting_a_parent_action_sets_the_child_parent_to_null(): void
    {
        $this->insertRoutine();
        $this->insertAction('11111111-1111-4111-8111-111111111111');
        $this->insertAction('22222222-2222-4222-8222-222222222222', '11111111-1111-4111-8111-111111111111');

        DB::table('routine_actions')->where('routine_action_identifier', '11111111-1111-4111-8111-111111111111')->delete();

        $this->assertDatabaseHas('routine_actions', [
            'routine_action_identifier' => '22222222-2222-4222-8222-222222222222',
            'parent_routine_action_identifier' => null,
        ]);
    }

    public function test_rejects_an_action_with_a_missing_parent(): void
    {
        $this->insertRoutine();

        $this->expectException(QueryException::class);

        $this->insertAction('22222222-2222-4222-8222-222222222222', '11111111-1111-4111-8111-111111111111');
    }

    public function test_rejects_a_duplicate_action_primary_key(): void
    {
        $this->insertRoutine();
        $this->insertAction('11111111-1111-4111-8111-111111111111');

        $this->expectException(QueryException::class);

        $this->insertAction('11111111-1111-4111-8111-111111111111');
    }

    private function insertRoutine(): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'account_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'routine_name' => '制約確認',
        ]);
    }

    private function insertAction(string $identifier, ?string $parentIdentifier = null): void
    {
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => $identifier,
            'parent_routine_action_identifier' => $parentIdentifier,
            'routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'action_name' => '制約確認',
        ]);
    }
}
