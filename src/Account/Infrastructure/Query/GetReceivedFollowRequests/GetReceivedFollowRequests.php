<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetReceivedFollowRequests;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsInputPort;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsInterface;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsOutput;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class GetReceivedFollowRequests implements GetReceivedFollowRequestsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetReceivedFollowRequestsInputPort $input): GetReceivedFollowRequestsOutputPort
    {
        $followRequests = DB::table('follow_requests')
            ->join('accounts', 'follow_requests.requesting_account_identifier', '=', 'accounts.account_identifier')
            ->where('follow_requests.target_account_identifier', $input->targetAccountIdentifier())
            ->where('follow_requests.status', 'pending')
            ->orderByDesc('follow_requests.created_at')
            ->orderBy('follow_requests.requesting_account_identifier')
            ->get(['accounts.account_identifier', 'accounts.account_name', 'accounts.account_bio'])
            ->map(fn (object $account): array => [
                'accountIdentifier' => (string) $account->account_identifier,
                'accountName' => (string) $account->account_name,
                'accountBio' => $account->account_bio === null ? null : (string) $account->account_bio,
                'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier)),
            ])
            ->all();

        return new GetReceivedFollowRequestsOutput($followRequests);
    }
}
