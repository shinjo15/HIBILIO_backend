<?php

declare(strict_types=1);

namespace Tests\Feature\Notification\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAnnouncementsActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const FIRST = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const SECOND = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const TIME = '2026-11-07 12:00:00';

    public function test_announcements_are_separate_paginated_and_stable(): void
    {
        $this->account();
        DB::table('personal_notifications')->insert(['notification_identifier' => self::FIRST, 'receiver_account_identifier' => self::ACCOUNT, 'status' => 'unread', 'content' => '個別', 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        $content = str_repeat('🌸本文', 501);
        foreach ([self::SECOND, self::FIRST] as $id) {
            DB::table('system_announcements')->insert(['announcement_identifier' => $id, 'title' => 'お知らせ', 'content' => $content, 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        }
        $one = $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements?page=1&number_of_items_per_page=1&account_identifier=forged');
        $one->assertOk()->assertJsonPath('total', 2)->assertJsonPath('announcements.0.announcement_identifier', self::FIRST)->assertJsonPath('announcements.0.content', $content);
        self::assertEqualsCanonicalizing(['announcement_identifier', 'title', 'content', 'created_at'], array_keys($one->json('announcements.0')));
        $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements?page=2&number_of_items_per_page=1')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('announcements.0.announcement_identifier', self::SECOND);
        $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements?page=3&number_of_items_per_page=1')->assertOk()->assertExactJson(['announcements' => [], 'total' => 2]);
    }

    public function test_empty_and_authentication(): void
    {
        $this->getJson('/api/announcements')->assertUnauthorized();
        $this->account();
        $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements')->assertOk()->assertExactJson(['announcements' => [], 'total' => 0]);
        foreach ([['available' => true, 'status' => 'temporarily_banned'], ['available' => false, 'status' => 'active']] as $state) {
            DB::table('accounts')->where('account_identifier', self::ACCOUNT)->update($state);
            $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements')->assertUnauthorized()->assertSessionMissing('account_identifier');
        }
    }

    public function test_newer_announcements_come_first_for_each_account(): void
    {
        $this->account();
        $other = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $other, 'account_name' => '別の利用者', 'email_address' => 'other@example.com', 'available' => true, 'status' => 'active', 'created_at' => self::TIME, 'updated_at' => self::TIME]);
        foreach ([[self::FIRST, self::TIME], [self::SECOND, '2026-11-08 12:00:00']] as [$id, $time]) {
            DB::table('system_announcements')->insert(['announcement_identifier' => $id, 'title' => 'お知らせ', 'content' => '本文', 'created_at' => $time, 'updated_at' => $time]);
        }
        foreach ([self::ACCOUNT, $other] as $account) {
            $this->withSession(['account_identifier' => $account])->getJson('/api/announcements')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('announcements.0.announcement_identifier', self::SECOND)->assertJsonPath('announcements.1.announcement_identifier', self::FIRST);
        }
    }

    public function test_pagination_validation_uses_existing_limits(): void
    {
        $this->account();
        foreach (['page=0', 'page=1%20OR%201%3D1', 'number_of_items_per_page=0', 'number_of_items_per_page=121'] as $query) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements?'.$query)->assertUnprocessable();
        }
        $this->withSession(['account_identifier' => self::ACCOUNT])->getJson('/api/announcements?number_of_items_per_page=120')->assertOk();
    }

    private function account(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => '通知先', 'email_address' => 'notifications@example.com', 'available' => true, 'status' => 'active', 'created_at' => self::TIME, 'updated_at' => self::TIME]);
    }
}
