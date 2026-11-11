<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class RoutineActionPositionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if (! defined('HIBILIO_POSTGRES_REGRESSION')) {
            $connection = $app['config']->get('database.connections.sqlite');
            if ($app['config']->get('database.default') !== 'sqlite'
                || ($connection['driver'] ?? null) !== 'sqlite'
                || ($connection['database'] ?? null) !== ':memory:'
                || ! empty($connection['url'])
                || isset($connection['read']) || isset($connection['write'])) {
                throw new RuntimeException('Migration tests require an in-memory SQLite database or the guarded PostgreSQL fixture.');
            }
        }

        return $app;
    }

    public function test_adds_column_and_backfills_existing_sqlite_rows_without_removing_relations(): void
    {
        $default = DB::getDefaultConnection();
        config(['database.connections.position_migration_fixture' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('position_migration_fixture');

        try {
            Schema::create('routine_actions', function (Blueprint $table): void {
                $table->string('routine_action_identifier')->primary();
                $table->string('routine_identifier');
                $table->string('action_name');
                $table->timestamp('created_at')->nullable();
            });
            Schema::create('selected_actions', function (Blueprint $table): void {
                $table->string('routine_action_identifier');
                $table->foreign('routine_action_identifier')->references('routine_action_identifier')->on('routine_actions');
            });
            DB::table('routine_actions')->insert([
                ['routine_action_identifier' => 'b', 'routine_identifier' => 'r', 'action_name' => 'keep B', 'created_at' => null],
                ['routine_action_identifier' => 'a', 'routine_identifier' => 'r', 'action_name' => 'keep A', 'created_at' => '2026-01-01 00:00:00'],
            ]);
            DB::table('selected_actions')->insert(['routine_action_identifier' => 'b']);

            $migration = require database_path('migrations/2026_11_11_000000_add_position_to_routine_actions.php');
            $migration->up();

            self::assertSame(0, DB::table('routine_actions')->where('routine_action_identifier', 'a')->value('position'));
            self::assertSame(1, DB::table('routine_actions')->where('routine_action_identifier', 'b')->value('position'));
            self::assertSame('keep B', DB::table('routine_actions')->where('routine_action_identifier', 'b')->value('action_name'));
            self::assertSame('b', DB::table('selected_actions')->value('routine_action_identifier'));
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('position_migration_fixture');
        }
    }

    public function test_backfills_each_routine_without_losing_existing_data(): void
    {
        $migration = require database_path('migrations/2026_11_11_000000_add_position_to_routine_actions.php');
        $routines = ['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'];
        foreach ($routines as $routine) {
            DB::table('routines')->insert([
                'routine_identifier' => $routine,
                'account_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                'routine_name' => 'legacy',
            ]);
        }
        $rows = [
            ['33333333-3333-4333-8333-333333333333', $routines[0], '2026-01-02 00:00:00'],
            ['22222222-2222-4222-8222-222222222222', $routines[0], '2026-01-01 00:00:00'],
            ['11111111-1111-4111-8111-111111111111', $routines[0], '2026-01-01 00:00:00'],
            ['44444444-4444-4444-8444-444444444444', $routines[0], null],
            ['55555555-5555-4555-8555-555555555555', $routines[1], '2026-01-01 00:00:00'],
        ];
        foreach ($rows as [$id, $routine, $createdAt]) {
            DB::table('routine_actions')->insert([
                'routine_action_identifier' => $id,
                'routine_identifier' => $routine,
                'action_name' => $id,
                'action_memo' => 'preserve',
                'video_url' => 'https://example.com/video',
                'created_at' => $createdAt,
            ]);
        }
        DB::table('routine_actions')->where('routine_action_identifier', $rows[0][0])->update(['parent_routine_action_identifier' => $rows[1][0]]);
        DB::table('accounts')->insert([
            'account_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'account_name' => 'owner',
            'email_address' => 'legacy@example.com',
            'status' => 'active',
        ]);
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'executor_account_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'routine_identifier' => $routines[0],
            'executed_at' => '2026-01-03 00:00:00',
        ]);
        DB::table('routine_execution_actions')->insert([
            'routine_execution_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'routine_action_identifier' => $rows[0][0],
        ]);
        DB::table('routine_actions')->where('routine_action_identifier', $rows[1][0])->update(['position' => 1]);
        DB::table('routine_actions')->where('routine_action_identifier', $rows[4][0])->update(['position' => 7]);

        $migration->up();
        $migration->up();

        foreach ([$rows[2][0] => 0, $rows[1][0] => 1, $rows[0][0] => 2, $rows[3][0] => 3, $rows[4][0] => 7] as $id => $position) {
            $this->assertDatabaseHas('routine_actions', [
                'routine_action_identifier' => $id,
                'position' => $position,
                'action_memo' => 'preserve',
                'video_url' => 'https://example.com/video',
            ]);
        }
        $this->assertDatabaseHas('routine_actions', [
            'routine_action_identifier' => $rows[0][0],
            'parent_routine_action_identifier' => $rows[1][0],
        ]);
        $this->assertDatabaseHas('routine_execution_actions', [
            'routine_execution_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'routine_action_identifier' => $rows[0][0],
        ]);
        self::assertSame(5, DB::table('routine_actions')->count());
    }
}
