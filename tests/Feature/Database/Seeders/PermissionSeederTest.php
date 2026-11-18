<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    private const GROUP = 'e0000000-0000-4000-8000-000000000001';

    private const NAMES = [
        'accountRead', 'accountUpdate', 'accountWrite', 'contactRead', 'contactWrite',
        'tagUpdate', 'announcementRead', 'announcementWrite', 'permissionGroupRead',
        'permissionGroupUpdate', 'permissionGroupWrite',
    ];

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
            throw new RuntimeException('Permission seeder tests require an in-memory SQLite database with foreign keys enabled.');
        }

        return $app;
    }

    public function test_permission_seeder_registers_exact_permissions_without_creating_users_or_admins(): void
    {
        self::assertSame(0, Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]));

        self::assertEqualsCanonicalizing(self::NAMES, DB::table('functions')->pluck('function_name')->all());
        foreach (self::NAMES as $index => $name) {
            $this->assertDatabaseHas('functions', [
                'function_identifier' => sprintf('f0000000-0000-4000-8000-%012d', $index + 1),
                'function_name' => $name,
            ]);
        }
        self::assertSame(1, DB::table('permission_groups')->count());
        $this->assertDatabaseHas('permission_groups', ['permission_group_identifier' => self::GROUP, 'permission_group_name' => '全機能']);
        self::assertEqualsCanonicalizing(self::NAMES, DB::table('functions')
            ->join('function_permission_group', 'functions.function_identifier', '=', 'function_permission_group.function_identifier')
            ->where('permission_group_identifier', self::GROUP)->pluck('function_name')->all());
        self::assertSame(11, DB::table('function_permission_group')->count());
        self::assertSame(0, DB::table('admins')->count());
        self::assertSame(0, DB::table('users')->count());
        self::assertSame(0, DB::table('admin_permission_group')->count());
    }

    public function test_rerun_restores_missing_association_without_changing_existing_records(): void
    {
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
        $function = DB::table('functions')->where('function_name', 'accountRead')->value('function_identifier');
        $customFunction = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $customGroup = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $admin = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        DB::table('functions')->insert(['function_identifier' => $customFunction, 'function_name' => 'customAction']);
        DB::table('permission_groups')->insert(['permission_group_identifier' => $customGroup, 'permission_group_name' => '独自グループ']);
        DB::table('admins')->insert(['admin_identifier' => $admin, 'admin_name' => '管理者', 'email_address' => 'admin@example.com']);
        DB::table('admin_permission_group')->insert([
            ['admin_identifier' => $admin, 'permission_group_identifier' => self::GROUP],
            ['admin_identifier' => $admin, 'permission_group_identifier' => $customGroup],
        ]);
        DB::table('function_permission_group')->insert([
            ['function_identifier' => $function, 'permission_group_identifier' => $customGroup],
        ]);
        $tables = ['functions', 'permission_groups', 'admins', 'admin_permission_group', 'function_permission_group', 'users'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->get()->all();
        }
        $before['function_permission_group'][] = (object) ['function_identifier' => $customFunction, 'permission_group_identifier' => self::GROUP];
        DB::table('function_permission_group')->where(['function_identifier' => $function, 'permission_group_identifier' => self::GROUP])->delete();

        self::assertSame(0, Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]));
        self::assertSame(0, Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]));

        foreach ($tables as $table) {
            self::assertEqualsCanonicalizing($before[$table], DB::table($table)->get()->all(), $table);
        }
    }

    public function test_conflicting_name_rolls_back_without_changing_existing_data(): void
    {
        DB::table('functions')->insert(['function_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'function_name' => 'permissionGroupWrite']);
        $before = DB::table('functions')->get()->all();

        try {
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            self::fail('A conflicting function name must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Initial function name conflicts', $exception->getMessage());
        }

        self::assertEquals($before, DB::table('functions')->get()->all());
        self::assertSame(0, DB::table('permission_groups')->count());
        self::assertSame(0, DB::table('function_permission_group')->count());
    }

    public function test_conflicting_group_name_rolls_back_seeded_functions(): void
    {
        DB::table('permission_groups')->insert(['permission_group_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'permission_group_name' => '全機能']);
        try {
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            self::fail('A conflicting group name must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Initial permission group name conflicts', $exception->getMessage());
        }
        self::assertSame(0, DB::table('functions')->count());
        self::assertSame(1, DB::table('permission_groups')->count());
    }

    public function test_conflicting_group_identifier_does_not_receive_permissions(): void
    {
        DB::table('permission_groups')->insert(['permission_group_identifier' => self::GROUP, 'permission_group_name' => '独自グループ']);
        try {
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            self::fail('A conflicting group identifier must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Initial permission group identifier conflicts', $exception->getMessage());
        }
        self::assertSame(0, DB::table('functions')->count());
        self::assertSame(0, DB::table('function_permission_group')->count());
        $this->assertDatabaseHas('permission_groups', ['permission_group_identifier' => self::GROUP, 'permission_group_name' => '独自グループ']);
    }

    public function test_duplicate_group_name_with_fixed_identifier_rolls_back_all_changes(): void
    {
        $otherGroup = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $customFunction = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $admin = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        DB::table('permission_groups')->insert([
            ['permission_group_identifier' => self::GROUP, 'permission_group_name' => '全機能'],
            ['permission_group_identifier' => $otherGroup, 'permission_group_name' => '全機能'],
        ]);
        DB::table('functions')->insert(['function_identifier' => $customFunction, 'function_name' => 'customAction']);
        DB::table('admins')->insert(['admin_identifier' => $admin, 'admin_name' => '管理者', 'email_address' => 'admin@example.com']);
        DB::table('admin_permission_group')->insert(['admin_identifier' => $admin, 'permission_group_identifier' => self::GROUP]);
        DB::table('function_permission_group')->insert(['function_identifier' => $customFunction, 'permission_group_identifier' => $otherGroup]);
        $tables = ['functions', 'permission_groups', 'admins', 'admin_permission_group', 'function_permission_group', 'users'];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->get()->all();
        }

        try {
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            self::fail('A duplicate group name must be rejected even when the fixed identifier exists.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Initial permission group name conflicts', $exception->getMessage());
        }

        foreach ($tables as $table) {
            self::assertEqualsCanonicalizing($before[$table], DB::table($table)->get()->all(), $table);
        }
    }
}
