<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Infrastructure\Query\GetSentFollowRequests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInput;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInterface;
use Src\Account\Infrastructure\Query\GetSentFollowRequests\GetSentFollowRequests;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetSentFollowRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_pending_and_rejected_follow_requests_sent_by_the_requesting_account_in_stable_order(): void
    {
        $requesting = '11111111-1111-4111-8111-111111111111';
        $olderPendingTarget = '22222222-2222-4222-8222-222222222222';
        $sameTimeRejectedTarget = '33333333-3333-4333-8333-333333333333';
        $sameTimePendingTarget = '44444444-4444-4444-8444-444444444444';
        $approvedTarget = '55555555-5555-4555-8555-555555555555';
        $otherRequesting = '66666666-6666-4666-8666-666666666666';
        $otherTarget = '77777777-7777-4777-8777-777777777777';
        $older = now()->subMinute();
        $sameTime = now();

        $this->insertAccount($requesting, '申請者');
        $this->insertAccount($olderPendingTarget, '古い申請先', '古い自己紹介');
        $this->insertAccount($sameTimeRejectedTarget, '却下済み申請先');
        $this->insertAccount($sameTimePendingTarget, '保留中申請先', '保留中自己紹介');
        $this->insertAccount($approvedTarget, '承認済み申請先');
        $this->insertAccount($otherRequesting, '他の申請者');
        $this->insertAccount($otherTarget, '他の申請先');
        $this->insertFollowRequest($requesting, $olderPendingTarget, 'pending', $older);
        $this->insertFollowRequest($requesting, $sameTimePendingTarget, 'pending', $sameTime);
        $this->insertFollowRequest($requesting, $sameTimeRejectedTarget, 'rejected', $sameTime);
        $this->insertFollowRequest($requesting, $approvedTarget, 'approved', $sameTime);
        $this->insertFollowRequest($otherRequesting, $otherTarget, 'pending', $sameTime);

        $result = $this->app->make(GetSentFollowRequestsInterface::class)->execute(new GetSentFollowRequestsInput($requesting));

        self::assertInstanceOf(GetSentFollowRequests::class, $this->app->make(GetSentFollowRequestsInterface::class));
        self::assertSame([
            [
                'accountIdentifier' => $sameTimeRejectedTarget,
                'accountName' => '却下済み申請先',
                'accountBio' => null,
                'iconImageUrl' => "https://images.example/accounts/{$sameTimeRejectedTarget}/icon",
                'headerImageUrl' => "https://images.example/accounts/{$sameTimeRejectedTarget}/header",
            ],
            [
                'accountIdentifier' => $sameTimePendingTarget,
                'accountName' => '保留中申請先',
                'accountBio' => '保留中自己紹介',
                'iconImageUrl' => "https://images.example/accounts/{$sameTimePendingTarget}/icon",
                'headerImageUrl' => "https://images.example/accounts/{$sameTimePendingTarget}/header",
            ],
            [
                'accountIdentifier' => $olderPendingTarget,
                'accountName' => '古い申請先',
                'accountBio' => '古い自己紹介',
                'iconImageUrl' => "https://images.example/accounts/{$olderPendingTarget}/icon",
                'headerImageUrl' => "https://images.example/accounts/{$olderPendingTarget}/header",
            ],
        ], $result->followRequests());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(AccountImageUrlServiceInterface::class, new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return "https://images.example/accounts/{$accountIdentifier->value()}/icon";
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return "https://images.example/accounts/{$accountIdentifier->value()}/header";
            }
        });
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
