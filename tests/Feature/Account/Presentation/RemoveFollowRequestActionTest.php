<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RemoveFollowRequestActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_removes_a_pending_follow_request_without_affecting_follows(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target, 'pending');

        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$target}")->assertNoContent();

        $this->assertDatabaseMissing('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target]);
        $this->assertDatabaseMissing('follows', ['following_account_identifier' => $requesting, 'followed_account_identifier' => $target]);
    }

    public function test_removes_a_rejected_follow_request(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target, 'rejected');

        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$target}")->assertNoContent();

        $this->assertDatabaseMissing('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target]);
    }

    public function test_returns_not_found_for_an_approved_follow_request_without_affecting_the_follow(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target, 'approved');
        DB::table('follows')->insert(['following_account_identifier' => $requesting, 'followed_account_identifier' => $target, 'created_at' => now(), 'updated_at' => now()]);

        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$target}")->assertNotFound();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'approved']);
        $this->assertDatabaseHas('follows', ['following_account_identifier' => $requesting, 'followed_account_identifier' => $target]);
    }

    public function test_returns_not_found_for_a_missing_or_another_accounts_follow_request(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $otherRequesting = '33333333-3333-4333-8333-333333333333';
        $target = '22222222-2222-4222-8222-222222222222';
        $missingTarget = '44444444-4444-4444-8444-444444444444';
        $this->account($requesting, '申請者');
        $this->account($otherRequesting, '別の申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($otherRequesting, $target, 'pending');

        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$missingTarget}")->assertNotFound();
        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$target}")->assertNotFound();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $otherRequesting, 'target_account_identifier' => $target, 'status' => 'pending']);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->deleteJson('/api/follow-requests/22222222-2222-4222-8222-222222222222')->assertUnauthorized();
    }

    public function test_allows_requesting_again_after_removing_a_follow_request(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $target = '22222222-2222-4222-8222-222222222222';
        $this->account($requesting, '申請者');
        $this->account($target, '鍵Account', 'private');
        $this->request($requesting, $target, 'rejected');

        $this->withSession(['account_identifier' => $requesting])->deleteJson("/api/follow-requests/{$target}")->assertNoContent();
        $this->withSession(['account_identifier' => $requesting])->postJson('/api/follows', ['followed_account_identifier' => $target])->assertNoContent();

        $this->assertDatabaseHas('follow_requests', ['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => 'pending']);
    }

    private function account(string $identifier, string $name, string $visibility = 'public'): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'email_address' => "{$identifier}@example.com", 'status' => 'active', 'visibility' => $visibility, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function request(string $requesting, string $target, string $status): void
    {
        DB::table('follow_requests')->insert(['requesting_account_identifier' => $requesting, 'target_account_identifier' => $target, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);
    }
}
