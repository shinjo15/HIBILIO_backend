<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetSentFollowRequests;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInputPort;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInterface;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsOutput;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsOutputList;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class GetSentFollowRequests implements GetSentFollowRequestsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetSentFollowRequestsInputPort $input): GetSentFollowRequestsOutputPort
    {
        $followRequests = DB::table('follow_requests')
            ->join('accounts', 'follow_requests.target_account_identifier', '=', 'accounts.account_identifier')
            ->where('follow_requests.requesting_account_identifier', $input->requestingAccountIdentifier())
            ->whereIn('follow_requests.status', ['pending', 'rejected'])
            ->orderByDesc('follow_requests.created_at')
            ->orderBy('follow_requests.target_account_identifier')
            ->get(['accounts.account_identifier', 'accounts.account_name', 'accounts.account_bio'])
            ->map(fn (object $account): GetSentFollowRequestsOutput => new GetSentFollowRequestsOutput(
                (string) $account->account_identifier,
                (string) $account->account_name,
                $account->account_bio === null ? null : (string) $account->account_bio,
                $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier)),
                $this->accountImageUrlService->headerImageUrl(new AccountIdentifier((string) $account->account_identifier)),
            ))
            ->all();

        return new GetSentFollowRequestsOutputList($followRequests);
    }
}
