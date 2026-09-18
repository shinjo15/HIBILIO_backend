<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetReceivedFollowRequestsActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_only_pending_follow_requests_received_by_the_authenticated_account(): void
    {
        $target = '11111111-1111-4111-8111-111111111111';
        $olderPending = '22222222-2222-4222-8222-222222222222';
        $rejected = '33333333-3333-4333-8333-333333333333';
        $otherTarget = '44444444-4444-4444-8444-444444444444';
        $newerPending = '55555555-5555-4555-8555-555555555555';
        $this->account($target, '受信者');
        $this->account($olderPending, '古い申請者', '古い申請者の説明');
        $this->account($rejected, '却下済み申請者');
        $this->account($otherTarget, '別の受信者');
        $this->account($newerPending, '新しい申請者');
        $this->followRequest($olderPending, $target, 'pending', now()->subMinute());
        $this->followRequest($newerPending, $target, 'pending', now());
        $this->followRequest($rejected, $target, 'rejected');
        $this->followRequest($olderPending, $otherTarget, 'pending');

        $this->withSession(['account_identifier' => $target])
            ->getJson('/api/my/follow-requests')
            ->assertOk()
            ->assertExactJson(['follow_requests' => [
                [
                    'account_identifier' => $newerPending,
                    'account_name' => '新しい申請者',
                    'account_bio' => null,
                    'icon_image_url' => "https://images.example/accounts/{$newerPending}/icon",
                ],
                [
                    'account_identifier' => $olderPending,
                    'account_name' => '古い申請者',
                    'account_bio' => '古い申請者の説明',
                    'icon_image_url' => "https://images.example/accounts/{$olderPending}/icon",
                ],
            ]]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/my/follow-requests')->assertUnauthorized();
    }

    private function account(string $identifier, string $name, ?string $bio = null): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'account_bio' => $bio, 'email_address' => "$identifier@example.com", 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function followRequest(string $requestingAccountIdentifier, string $targetAccountIdentifier, string $status, ?\DateTimeInterface $createdAt = null): void
    {
        $createdAt ??= now();
        DB::table('follow_requests')->insert(['requesting_account_identifier' => $requestingAccountIdentifier, 'target_account_identifier' => $targetAccountIdentifier, 'status' => $status, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }
}
