<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FollowRequestActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_pending_request_without_creating_a_follow_for_a_private_account(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');

        $this->withSession(['account_identifier' => $requesting])->postJson('/api/follows', ['followed_account_identifier' => $target])->assertNoContent();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'pending']);
        $this->assertDatabaseMissing('follows', ['following_account_identifier' => $requesting, 'followed_account_identifier' => $target]);
    }

    public function test_approves_a_pending_request_and_creates_a_follow(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target);

        $this->withSession(['account_identifier' => $target])->postJson("/api/follow-requests/{$requesting}/approve")->assertNoContent();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'approved']);
        $this->assertDatabaseHas('follows', ['following_account_identifier' => $requesting, 'followed_account_identifier' => $target]);
    }

    public function test_rejects_a_pending_request_without_creating_a_follow(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target);

        $this->withSession(['account_identifier' => $target])->postJson("/api/follow-requests/{$requesting}/reject")->assertNoContent();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'rejected']);
        $this->assertDatabaseMissing('follows', ['following_account_identifier' => $requesting, 'followed_account_identifier' => $target]);
    }

    private function account(string $identifier, string $name, string $visibility = 'public'): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'email_address' => "{$identifier}@example.com", 'status' => 'active', 'visibility' => $visibility, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function request(string $requesting, string $target): void
    {
        DB::table('follow_requests')->insert(['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
    }
}
