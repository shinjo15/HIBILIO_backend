<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetMyNotificationsActionTest extends TestCase
{
    use RefreshDatabase;

    private const RECEIVER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const FIRST = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const SECOND = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const THIRD = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const TIME = '2026-11-07 12:00:00';

    public function test_only_authenticated_accounts_personal_notifications_are_returned(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAccount(self::OTHER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '自分への通知');
        $this->insertPersonalNotification(self::SECOND, self::OTHER, '他人への通知');

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications?account_identifier='.self::OTHER)
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.notification_identifier', self::FIRST);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/my/notifications')->assertUnauthorized();
    }

    public function test_global_pages_are_stable_for_equal_timestamps_and_overlapping_identifiers(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAccount(self::OTHER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '最初の通知');
        $this->insertPersonalNotification(self::SECOND, self::RECEIVER, '次の通知', 'read');
        $this->insertPersonalNotification(self::THIRD, self::OTHER, '他人への通知');
        $this->insertAnnouncement(self::FIRST, 'お知らせ１', self::TIME);
        $this->insertAnnouncement(self::THIRD, 'お知らせ２', self::TIME);
        $this->insertAnnouncement('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '古いお知らせ', '2026-11-07 09:00:00');

        $one = $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications?page=1&number_of_items_per_page=2');
        $two = $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications?page=2&number_of_items_per_page=2');
        $three = $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications?page=3&number_of_items_per_page=2');
        $one->assertOk()->assertJsonPath('total', 5)->assertJsonCount(2, 'notifications')->assertJsonPath('notifications.0.notification_identifier', self::FIRST)->assertJsonPath('notifications.0.category', 'personal')->assertJsonPath('notifications.0.content', '最初の通知')->assertJsonPath('notifications.1.notification_identifier', self::SECOND)->assertJsonPath('notifications.1.status', 'read');
        $two->assertOk()->assertJsonPath('total', 5)->assertJsonCount(2, 'notifications')->assertJsonPath('notifications.0.notification_identifier', self::FIRST)->assertJsonPath('notifications.0.category', 'system_announcement')->assertJsonPath('notifications.0.status', null)->assertJsonPath('notifications.0.title', 'お知らせ１')->assertJsonPath('notifications.1.notification_identifier', self::THIRD);
        $three->assertOk()->assertJsonPath('total', 5)->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.notification_identifier', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
        foreach ([$one->json('notifications.0'), $two->json('notifications.0')] as $notification) {
            self::assertEqualsCanonicalizing(['notification_identifier', 'category', 'status', 'title', 'content', 'created_at'], array_keys($notification));
        }
    }

    public function test_returns_an_empty_inbox_when_there_are_no_notifications_or_announcements(): void
    {
        $this->insertAccount(self::RECEIVER);

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications')
            ->assertOk()
            ->assertExactJson(['notifications' => [], 'total' => 0]);
    }

    public function test_returns_system_announcements_without_personal_notifications(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAnnouncement(self::FIRST, 'お知らせ', self::TIME);

        $response = $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications');
        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.notification_identifier', self::FIRST)
            ->assertJsonPath('notifications.0.category', 'system_announcement')
            ->assertJsonPath('notifications.0.status', null)
            ->assertJsonPath('notifications.0.title', 'お知らせ')
            ->assertJsonPath('notifications.0.content', 'お知らせ本文');
        self::assertEqualsCanonicalizing(['notification_identifier', 'category', 'status', 'title', 'content', 'created_at'], array_keys($response->json('notifications.0')));
    }

    public function test_out_of_range_page_returns_empty_notifications_and_global_total(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '個別通知');
        $this->insertAnnouncement(self::SECOND, 'お知らせ', self::TIME);

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications?page=3&number_of_items_per_page=1')
            ->assertOk()
            ->assertExactJson(['notifications' => [], 'total' => 2]);
    }

    public function test_system_announcement_supports_long_unicode_content(): void
    {
        $this->insertAccount(self::RECEIVER);
        $title = str_repeat('題', 101);
        $content = str_repeat('内', 501);
        DB::table('system_announcements')->insert(['announcement_identifier' => self::FIRST, 'title' => $title, 'content' => $content, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications')->assertOk()->assertJsonPath('notifications.0.title', $title)->assertJsonPath('notifications.0.content', $content);
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
