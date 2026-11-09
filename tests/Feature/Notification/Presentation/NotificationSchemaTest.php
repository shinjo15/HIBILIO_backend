<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Presentation;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class NotificationSchemaTest extends TestCase
{
    use RefreshDatabase;

    private const RECEIVER = '11111111-1111-4111-8111-111111111111';

    private const FIRST = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const TIME = '2026-11-07 12:00:00';

    public function test_personal_notification_schema_stores_content_without_post_actor_or_type(): void
    {
        self::assertTrue(Schema::hasColumn('personal_notifications', 'content'));
        foreach (['post_identifier', 'actor_account_identifier', 'type'] as $column) {
            self::assertFalse(Schema::hasColumn('personal_notifications', $column));
        }
        $foreignKeys = Schema::getForeignKeys('personal_notifications');
        self::assertCount(1, $foreignKeys);
        self::assertSame(['receiver_account_identifier'], $foreignKeys[0]['columns']);
    }

    public function test_created_at_is_required_for_both_tables(): void
    {
        $this->insertAccount(self::RECEIVER);
        $rows = [
            'personal_notifications' => ['notification_identifier' => self::FIRST, 'receiver_account_identifier' => self::RECEIVER, 'content' => '通知', 'status' => 'unread', 'updated_at' => self::TIME],
            'system_announcements' => ['announcement_identifier' => self::FIRST, 'title' => '題', 'content' => 'お知らせ', 'updated_at' => self::TIME],
        ];
        foreach ($rows as $table => $row) {
            try {
                DB::transaction(static fn () => DB::table($table)->insert($row));
                self::fail("{$table}.created_at must be required");
            } catch (QueryException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_like_notification_setting_defaults_to_false_for_existing_and_new_accounts(): void
    {
        $migration = require database_path('migrations/2026_11_09_000000_add_like_notifications_enabled_to_accounts_table.php');
        $migration->down();
        $this->insertAccount(self::RECEIVER);
        try {
            $migration->up();
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::RECEIVER, 'like_notifications_enabled' => false]);
            $this->insertAccount(self::FIRST);
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::FIRST, 'like_notifications_enabled' => false]);
        } finally {
            if (! Schema::hasColumn('accounts', 'like_notifications_enabled')) {
                $migration->up();
            }
        }
    }

    public function test_notification_migration_up_and_down_preserve_existing_accounts(): void
    {
        $migration = require database_path('migrations/2026_11_07_132115_create_notification_tables.php');
        $this->insertAccount(self::RECEIVER);
        try {
            $migration->down();
            self::assertFalse(Schema::hasTable('personal_notifications'));
            self::assertFalse(Schema::hasTable('system_announcements'));
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::RECEIVER]);
            $migration->up();
            self::assertTrue(Schema::hasTable('personal_notifications'));
            self::assertTrue(Schema::hasTable('system_announcements'));
            self::assertFalse(Schema::hasColumn('personal_notifications', 'post_identifier'));
            $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '復元後の通知');
            $this->insertAnnouncement(self::FIRST, '復元後のお知らせ', self::TIME);
        } finally {
            if (! Schema::hasTable('personal_notifications')) {
                $migration->up();
            }
        }
    }

    private function insertAccount(string $identifier): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => '通知先', 'email_address' => "{$identifier}@example.com", 'available' => true, 'status' => 'active', 'created_at' => self::TIME, 'updated_at' => self::TIME]);
    }

    private function insertPersonalNotification(string $identifier, string $receiver, string $content, string $status = 'unread'): void
    {
        DB::table('personal_notifications')->insert(['notification_identifier' => $identifier, 'receiver_account_identifier' => $receiver, 'content' => $content, 'status' => $status, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
    }

    private function insertAnnouncement(string $identifier, string $title, string $time): void
    {
        DB::table('system_announcements')->insert(['announcement_identifier' => $identifier, 'title' => $title, 'content' => 'お知らせ本文', 'created_at' => $time, 'updated_at' => $time]);
    }
}
