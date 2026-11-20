<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class PermissionSeeder extends Seeder
{
    private const GROUP = 'e0000000-0000-4000-8000-000000000001';

    private const FUNCTIONS = [
        'f0000000-0000-4000-8000-000000000001' => 'accountRead',
        'f0000000-0000-4000-8000-000000000002' => 'accountUpdate',
        'f0000000-0000-4000-8000-000000000003' => 'accountWrite',
        'f0000000-0000-4000-8000-000000000004' => 'contactRead',
        'f0000000-0000-4000-8000-000000000005' => 'contactWrite',
        'f0000000-0000-4000-8000-000000000006' => 'tagUpdate',
        'f0000000-0000-4000-8000-000000000007' => 'announcementRead',
        'f0000000-0000-4000-8000-000000000008' => 'announcementWrite',
        'f0000000-0000-4000-8000-000000000009' => 'permissionGroupRead',
        'f0000000-0000-4000-8000-000000000010' => 'permissionGroupUpdate',
        'f0000000-0000-4000-8000-000000000011' => 'permissionGroupWrite',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::FUNCTIONS as $identifier => $name) {
                DB::table('functions')->insert(['function_identifier' => $identifier, 'function_name' => $name]);
            }

            DB::table('permission_groups')->insert(['permission_group_identifier' => self::GROUP, 'permission_group_name' => '全機能']);

            foreach (DB::table('functions')->pluck('function_identifier') as $identifier) {
                DB::table('function_permission_group')->insert(['function_identifier' => $identifier, 'permission_group_identifier' => self::GROUP]);
            }
        });
    }
}
