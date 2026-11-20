<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use Illuminate\Database\QueryException;
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

    public function test_duplicate_group_identifier_rolls_back_inserted_functions(): void
    {
        DB::table('permission_groups')->insert(['permission_group_identifier' => self::GROUP, 'permission_group_name' => '全機能']);

        try {
            Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
            self::fail('A duplicate primary key must fail.');
        } catch (QueryException $exception) {
            self::assertNotSame('', $exception->getMessage());
        }

        self::assertSame(0, DB::table('functions')->count());
        self::assertSame(0, DB::table('function_permission_group')->count());
        $this->assertDatabaseHas('permission_groups', ['permission_group_identifier' => self::GROUP, 'permission_group_name' => '全機能']);
    }

    public function test_preexisting_functions_are_included_in_all_permissions_group(): void
    {
        $customFunction = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('functions')->insert(['function_identifier' => $customFunction, 'function_name' => 'customAction']);

        self::assertSame(0, Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]));

        self::assertSame(12, DB::table('functions')->count());
        self::assertSame(12, DB::table('function_permission_group')->where('permission_group_identifier', self::GROUP)->count());
        $this->assertDatabaseHas('function_permission_group', ['function_identifier' => $customFunction, 'permission_group_identifier' => self::GROUP]);
    }
}
