<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Src\Notification\Domain\Entity\PersonalNotification;
use Src\Notification\Domain\Repository\PersonalNotificationRepositoryInterface;
use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Notification\Domain\ValueObject\PersonalNotificationStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class MarkPersonalNotificationAsReadActionTest extends TestCase
{
    use RefreshDatabase;

    private const RECEIVER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const FIRST = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const SECOND = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const THIRD = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const TIME = '2026-11-07 12:00:00';

    public function test_follow_like_unicode_notification_needs_only_receiver_and_can_be_read(): void
    {
        $this->insertAccount(self::RECEIVER);
        $content = '🌸 山田さんがあなたをフォローしました！';
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, $content);
        self::assertSame(1, DB::table('accounts')->count());
        self::assertSame(0, DB::table('posts')->count());

        $response = $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications');
        $response->assertOk()->assertJsonPath('total', 1)->assertJsonPath('notifications.0.content', $content)->assertJsonPath('notifications.0.status', 'unread');
        self::assertEqualsCanonicalizing(['notification_identifier', 'category', 'status', 'title', 'content', 'created_at'], array_keys($response->json('notifications.0')));
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertNoContent();
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertNoContent();
        $this->assertDatabaseHas('personal_notifications', ['notification_identifier' => self::FIRST, 'status' => 'read', 'content' => $content]);
    }

    public function test_marking_a_missing_personal_notification_as_read_returns_not_found(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAnnouncement(self::FIRST, '同じ識別子のお知らせ', self::TIME);

        $this->withSession(['account_identifier' => self::RECEIVER])
            ->patchJson('/api/my/notifications/'.self::FIRST.'/read')
            ->assertNotFound();
        $this->assertDatabaseHas('system_announcements', ['announcement_identifier' => self::FIRST]);
        self::assertSame(0, DB::table('personal_notifications')->count());
    }

    public function test_inactive_and_unavailable_session_accounts_cannot_access_notifications(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '未読通知');
        $this->insertAnnouncement(self::SECOND, 'お知らせ', self::TIME);

        foreach ([['available' => true, 'status' => 'temporarily_banned'], ['available' => false, 'status' => 'active']] as $state) {
            DB::table('accounts')->where('account_identifier', self::RECEIVER)->update($state);

            $this->withSession(['account_identifier' => self::RECEIVER])
                ->getJson('/api/my/notifications')
                ->assertUnauthorized()
                ->assertSessionMissing('account_identifier');
            $this->withSession(['account_identifier' => self::RECEIVER])
                ->patchJson('/api/my/notifications/'.self::FIRST.'/read')
                ->assertUnauthorized()
                ->assertSessionMissing('account_identifier');
            $this->assertDatabaseHas('personal_notifications', ['notification_identifier' => self::FIRST, 'status' => 'unread']);
        }
    }

    public function test_rejects_reading_another_receivers_notification(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertAccount(self::OTHER);
        $this->insertPersonalNotification(self::FIRST, self::OTHER, '他人への通知');
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications')->assertOk()->assertJsonPath('total', 0);
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertForbidden();
        $this->assertDatabaseHas('personal_notifications', ['notification_identifier' => self::FIRST, 'status' => 'unread']);
    }

    public function test_deleting_an_unrelated_post_preserves_the_notification(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, 'フォローのお知らせ');
        DB::table('routines')->insert(['routine_identifier' => self::SECOND, 'routine_name' => 'ルーティン', 'account_identifier' => self::RECEIVER, 'routine_execution_minutes' => 1, 'available' => true, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        DB::table('posts')->insert(['post_identifier' => self::THIRD, 'routine_identifier' => self::SECOND, 'post_category' => 'routine', 'post_like_count' => 0, 'post_support_count' => 0, 'available' => true, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        DB::table('posts')->where('post_identifier', self::THIRD)->delete();
        $this->assertDatabaseHas('personal_notifications', ['notification_identifier' => self::FIRST, 'content' => 'フォローのお知らせ']);
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications')->assertOk()->assertJsonPath('notifications.0.content', 'フォローのお知らせ');
    }

    public function test_unauthorized_and_invalid_pagination_requests(): void
    {
        $this->getJson('/api/my/notifications')->assertUnauthorized();
        $this->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertUnauthorized();
        $this->withSession(['account_identifier' => self::RECEIVER])->getJson('/api/my/notifications?page=1%20OR%201%3D1&number_of_items_per_page=2')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_rolls_back_read_when_persistence_fails(): void
    {
        $this->insertAccount(self::RECEIVER);
        $this->insertPersonalNotification(self::FIRST, self::RECEIVER, '通知');
        $this->app->instance(PersonalNotificationRepositoryInterface::class, new class implements PersonalNotificationRepositoryInterface
        {
            public function find(NotificationIdentifier $notificationIdentifier): ?PersonalNotification
            {
                return new PersonalNotification($notificationIdentifier, new AccountIdentifier('11111111-1111-4111-8111-111111111111'), '通知', PersonalNotificationStatus::Unread);
            }

            public function save(PersonalNotification $notification): void
            {
                DB::table('personal_notifications')->where('notification_identifier', $notification->notificationIdentifier()->value())->update(['status' => 'read']);
                throw new RuntimeException('persistence failed');
            }
        });
        $this->withSession(['account_identifier' => self::RECEIVER])->patchJson('/api/my/notifications/'.self::FIRST.'/read')->assertServerError();
        $this->assertDatabaseHas('personal_notifications', ['notification_identifier' => self::FIRST, 'status' => 'unread']);
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
