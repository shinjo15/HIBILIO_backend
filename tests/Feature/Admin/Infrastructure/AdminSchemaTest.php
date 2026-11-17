<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AdminSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_table_contains_only_the_specified_columns_and_has_no_account_dependency(): void
    {
        self::assertEqualsCanonicalizing([
            'admin_identifier', 'admin_name', 'email_address', 'create_at', 'updated_at',
            'create_user', 'updated_user', 'available',
        ], Schema::getColumnListing('admins'));
        foreach (Schema::getForeignKeys('admins') as $foreignKey) {
            self::assertNotSame('accounts', $foreignKey['foreign_table']);
        }

        DB::table('admins')->insert([
            'admin_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'admin_name' => '管理者',
            'email_address' => 'admin@example.com',
            'create_at' => '2026-11-17 12:00:00',
            'updated_at' => '2026-11-17 12:00:00',
            'create_user' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'updated_user' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'available' => false,
        ]);

        $this->assertDatabaseHas('admins', [
            'admin_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'admin_name' => '管理者',
            'email_address' => 'admin@example.com',
            'create_at' => '2026-11-17 12:00:00',
            'updated_at' => '2026-11-17 12:00:00',
            'create_user' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'updated_user' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'available' => false,
        ]);
    }

    public function test_admin_name_column_uses_the_same_type_as_account_name(): void
    {
        $adminColumns = collect(Schema::getColumns('admins'))->keyBy('name');
        $accountColumns = collect(Schema::getColumns('accounts'))->keyBy('name');

        self::assertSame($accountColumns['account_name']['type'], $adminColumns['admin_name']['type']);
    }

    public function test_management_columns_can_be_omitted_on_insert(): void
    {
        DB::table('admins')->insert([
            'admin_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'admin_name' => '管理者',
            'email_address' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('admins', [
            'admin_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'create_at' => null,
            'updated_at' => null,
            'create_user' => null,
            'updated_user' => null,
            'available' => true,
        ]);
    }

    public function test_migration_can_be_reverted_and_reapplied(): void
    {
        $migration = require database_path('migrations/2026_11_17_000000_create_admins_table.php');
        $migration->down();
        self::assertFalse(Schema::hasTable('admins'));
        $migration->up();
        self::assertTrue(Schema::hasTable('admins'));
    }
}
