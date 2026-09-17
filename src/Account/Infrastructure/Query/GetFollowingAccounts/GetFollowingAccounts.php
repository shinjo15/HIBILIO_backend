<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetFollowingAccounts;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInputPort;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInterface;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsOutput;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetFollowingAccounts implements GetFollowingAccountsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetFollowingAccountsInputPort $input): GetFollowingAccountsOutputPort
    {
        if ($input->viewerAccountIdentifier() !== null && BlockVisibility::exists($input->viewerAccountIdentifier(), $input->accountIdentifier())) {
            throw new BlockedAccountVisibilityException;
        }

        $query = DB::table('follows')
            ->join('accounts', 'follows.followed_account_identifier', '=', 'accounts.account_identifier')
            ->where('follows.following_account_identifier', $input->accountIdentifier())
            ->orderBy('follows.created_at')
            ->orderBy('follows.followed_account_identifier');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
        }

        $accounts = $query->get(['accounts.account_identifier', 'accounts.account_name', 'accounts.account_bio'])
            ->map(fn (object $account): array => ['accountIdentifier' => (string) $account->account_identifier, 'accountName' => (string) $account->account_name, 'accountBio' => $account->account_bio === null ? null : (string) $account->account_bio, 'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier))])
            ->all();

        return new GetFollowingAccountsOutput($accounts);
    }
}
