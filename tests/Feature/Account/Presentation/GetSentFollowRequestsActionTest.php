<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetSentFollowRequestsActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_sent_pending_and_rejected_follow_request_targets_without_exposing_status(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $pendingTarget = '22222222-2222-4222-8222-222222222222';
        $rejectedTarget = '33333333-3333-4333-8333-333333333333';
        $approvedTarget = '44444444-4444-4444-8444-444444444444';
        $otherRequesting = '55555555-5555-4555-8555-555555555555';
        $otherTarget = '66666666-6666-4666-8666-666666666666';
        $createdAt = now();

        $this->insertAccount($requesting, '申請者');
        $this->insertAccount($pendingTarget, '保留中申請先', '保留中自己紹介');
        $this->insertAccount($rejectedTarget, '却下済み申請先');
        $this->insertAccount($approvedTarget, '承認済み申請先');
        $this->insertAccount($otherRequesting, '他の申請者');
        $this->insertAccount($otherTarget, '他の申請先');
        $this->insertFollowRequest($requesting, $pendingTarget, 'pending', $createdAt);
        $this->insertFollowRequest($requesting, $rejectedTarget, 'rejected', $createdAt);
        $this->insertFollowRequest($requesting, $approvedTarget, 'approved', $createdAt);
        $this->insertFollowRequest($otherRequesting, $otherTarget, 'pending', $createdAt);

        $this->withSession(['account_identifier' => $requesting])
            ->getJson('/api/my/sent-follow-requests')
            ->assertOk()
            ->assertExactJson(['follow_requests' => [
                [
                    'account_identifier' => $pendingTarget,
                    'account_name' => '保留中申請先',
                    'account_bio' => '保留中自己紹介',
                    'icon_image_url' => "https://images.example/accounts/{$pendingTarget}/icon",
                    'header_image_url' => "https://images.example/accounts/{$pendingTarget}/header",
                ],
                [
                    'account_identifier' => $rejectedTarget,
                    'account_name' => '却下済み申請先',
                    'account_bio' => null,
                    'icon_image_url' => "https://images.example/accounts/{$rejectedTarget}/icon",
                    'header_image_url' => "https://images.example/accounts/{$rejectedTarget}/header",
                ],
            ]]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/my/sent-follow-requests')->assertUnauthorized();
    }

    private function insertAccount(string $identifier, string $name, ?string $bio = null): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => $bio,
            'email_address' => "{$identifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertFollowRequest(string $requestingAccountIdentifier, string $targetAccountIdentifier, string $status, \DateTimeInterface $createdAt): void
    {
        DB::table('follow_requests')->insert([
            'requesting_account_identifier' => $requestingAccountIdentifier,
            'target_account_identifier' => $targetAccountIdentifier,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
