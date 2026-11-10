<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GetMyUnreadNotificationCountActionTest extends TestCase
{
    use RefreshDatabase;

    private const RECEIVER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const FIRST = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const SECOND = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const THIRD = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const TIME = '2026-11-07 12:00:00';

    public function test_counts_only_the_authenticated_accounts_unread_personal_notifications(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAccount(self::OTHER);
        $this->insertNotification(self::FIRST, self::RECEIVER);
        $this->insertNotification(self::SECOND, self::RECEIVER, 'read');
        $this->insertNotification(self::THIRD, self::OTHER);
        $this->insertNotification('dddddddd-dddd-4ddd-8ddd-dddddddddddd', self::OTHER);
        DB::table('system_announcements')->insert(['announcement_identifier' => self::FIRST, 'title' => 'お知らせ', 'content' => '本文', 'created_at' => self::TIME, 'updated_at' => self::TIME]);

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications/unread-count?account_identifier='.self::OTHER)
            ->assertOk()->assertExactJson(['unread_count' => 1]);
    }

    public function test_zero_when_empty_or_all_notifications_are_read(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications/unread-count')->assertOk()->assertExactJson(['unread_count' => 0]);

        $this->insertNotification(self::FIRST, self::RECEIVER, 'read');
        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications/unread-count')->assertOk()->assertExactJson(['unread_count' => 0]);
    }

    public function test_counts_beyond_the_page_cap_using_an_aggregate(): void
    {
        $this->insertAccount(self::RECEIVER);
        for ($i = 0; $i < 121; $i++) {
            $this->insertNotification((string) Str::uuid(), self::RECEIVER);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->withSession(['account_identifier' => self::RECEIVER])
                ->getJson('/api/my/notifications/unread-count?page=2&number_of_items_per_page=1')
                ->assertOk()->assertExactJson(['unread_count' => 121]);
            $queries = array_values(array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains(strtolower($query['query']), 'personal_notifications')));
            self::assertCount(1, $queries);
            self::assertStringContainsString('count(', strtolower($queries[0]['query']));
            self::assertStringNotContainsString('limit', strtolower($queries[0]['query']));
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_marking_as_read_updates_the_count_and_preserves_the_list(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertNotification(self::FIRST, self::RECEIVER);
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications/unread-count')->assertExactJson(['unread_count' => 1]);
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertNoContent();
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertNoContent();
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications/unread-count')->assertExactJson(['unread_count' => 0]);
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('notifications.0.status', 'read');
    }

    public function test_unauthenticated_and_unknown_accounts_are_rejected(): void
    {
        $this->getJson('/api/my/notifications/unread-count')->assertUnauthorized();
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications/unread-count')->assertUnauthorized()->assertSessionMissing('account_identifier');
    }

    public function test_inactive_or_unavailable_accounts_are_rejected(): void
    {
        $this->insertAccount(self::RECEIVER);
        foreach ([['available' => true, 'status' => 'temporarily_banned'], ['available' => false, 'status' => 'active']] as $state) {
            DB::table('accounts')->where('account_identifier', self::RECEIVER)->update($state);
            $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications/unread-count')->assertUnauthorized()->assertSessionMissing('account_identifier');
        }
    }

    public function test_database_failure_returns_server_error_without_details(): void
    {
        $this->insertAccount(self::RECEIVER);
        Schema::drop('personal_notifications');

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications/unread-count')->assertServerError()->assertExactJson([]);
    }

    public function test_authentication_database_failure_is_not_mistaken_for_an_unauthorized_account(): void
    {
        Schema::rename('accounts', 'unavailable_accounts');

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->getJson('/api/my/notifications/unread-count')->assertServerError();
    }

    private function insertAccount(string $identifier): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => '通知先', 'email_address' => "{$identifier}@example.com", 'available' => true, 'status' => 'active', 'created_at' => self::TIME, 'updated_at' => self::TIME]);
    }

    private function insertNotification(string $identifier, string $receiver, string $status = 'unread'): void
    {
        DB::table('personal_notifications')->insert(['notification_identifier' => $identifier, 'receiver_account_identifier' => $receiver, 'content' => '通知', 'status' => $status, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
    }
}
