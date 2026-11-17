<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Infrastructure;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PermissionSchemaTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const GROUP = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const OTHER_GROUP = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const FUNCTION = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

    private const MISSING = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.connections.sqlite');
        if ($app['config']->get('database.default') !== 'sqlite'
            || ($connection['driver'] ?? null) !== 'sqlite'
            || ($connection['database'] ?? null) !== ':memory:'
            || ! empty($connection['url'])
            || isset($connection['read']) || isset($connection['write'])
            || ($connection['foreign_key_constraints'] ?? false) !== true) {
            throw new \RuntimeException('Permission schema tests require an in-memory SQLite database with foreign keys enabled.');
        }

        return $app;
    }

    public function test_multiple_groups_can_be_assigned_to_an_admin_and_share_a_function(): void
    {
        $this->insertRecords();
        DB::table('admin_permission_group')->insert([
            ['admin_identifier' => self::ADMIN, 'permission_group_identifier' => self::GROUP],
            ['admin_identifier' => self::ADMIN, 'permission_group_identifier' => self::OTHER_GROUP],
        ]);
        DB::table('function_permission_group')->insert([
            ['function_identifier' => self::FUNCTION, 'permission_group_identifier' => self::GROUP],
            ['function_identifier' => self::FUNCTION, 'permission_group_identifier' => self::OTHER_GROUP],
        ]);

        self::assertSame(2, DB::table('admin_permission_group')->where('admin_identifier', self::ADMIN)->count());
        self::assertSame(2, DB::table('function_permission_group')->where('function_identifier', self::FUNCTION)->count());
        self::assertEqualsCanonicalizing(['function_identifier', 'permission_group_identifier'], Schema::getColumnListing('function_permission_group'));
    }

    #[DataProvider('duplicateAssociations')]
    public function test_duplicate_association_is_rejected(string $table, array $association): void
    {
        $this->insertRecords();
        DB::table($table)->insert($association);

        $this->expectException(QueryException::class);
        DB::table($table)->insert($association);
    }

    public static function duplicateAssociations(): array
    {
        return [
            'admin group' => ['admin_permission_group', ['admin_identifier' => self::ADMIN, 'permission_group_identifier' => self::GROUP]],
            'group function' => ['function_permission_group', ['function_identifier' => self::FUNCTION, 'permission_group_identifier' => self::GROUP]],
        ];
    }

    #[DataProvider('missingReferences')]
    public function test_association_rejects_missing_references(string $table, array $association): void
    {
        $this->insertRecords();

        $this->expectException(QueryException::class);
        DB::table($table)->insert($association);
    }

    public static function missingReferences(): array
    {
        return [
            'missing admin' => ['admin_permission_group', ['admin_identifier' => self::MISSING, 'permission_group_identifier' => self::GROUP]],
            'missing admin group' => ['admin_permission_group', ['admin_identifier' => self::ADMIN, 'permission_group_identifier' => self::MISSING]],
            'missing function' => ['function_permission_group', ['function_identifier' => self::MISSING, 'permission_group_identifier' => self::GROUP]],
            'missing function group' => ['function_permission_group', ['function_identifier' => self::FUNCTION, 'permission_group_identifier' => self::MISSING]],
        ];
    }

    public function test_migration_can_be_reverted_and_reapplied(): void
    {
        $this->insertRecords();
        DB::table('admin_permission_group')->insert(['admin_identifier' => self::ADMIN, 'permission_group_identifier' => self::GROUP]);
        DB::table('function_permission_group')->insert(['function_identifier' => self::FUNCTION, 'permission_group_identifier' => self::GROUP]);

        $migration = require database_path('migrations/2026_11_17_000001_create_admin_permission_tables.php');
        $migration->down();
        foreach (['functions', 'permission_groups', 'admin_permission_group', 'function_permission_group'] as $table) {
            self::assertFalse(Schema::hasTable($table));
        }
        $migration->up();
        foreach (['functions', 'permission_groups', 'admin_permission_group', 'function_permission_group'] as $table) {
            self::assertTrue(Schema::hasTable($table));
        }
    }

    public function test_names_are_not_subject_to_an_arbitrary_short_limit(): void
    {
        $name = str_repeat('a', 300);
        DB::table('functions')->insert(['function_identifier' => self::FUNCTION, 'function_name' => $name]);
        DB::table('permission_groups')->insert(['permission_group_identifier' => self::GROUP, 'permission_group_name' => $name]);

        self::assertSame($name, DB::table('functions')->value('function_name'));
        self::assertSame($name, DB::table('permission_groups')->value('permission_group_name'));
    }

    private function insertRecords(): void
    {
        DB::table('admins')->insert(['admin_identifier' => self::ADMIN, 'admin_name' => '管理者', 'email_address' => 'admin@example.com']);
        DB::table('permission_groups')->insert([
            ['permission_group_identifier' => self::GROUP, 'permission_group_name' => 'グループ1'],
            ['permission_group_identifier' => self::OTHER_GROUP, 'permission_group_name' => 'グループ2'],
        ]);
        DB::table('functions')->insert(['function_identifier' => self::FUNCTION, 'function_name' => 'userUpdate']);
    }
}
