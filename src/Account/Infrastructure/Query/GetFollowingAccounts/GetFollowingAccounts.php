<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetFollowingAccounts;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInputPort;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInterface;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsOutput;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsOutputPort;

final class GetFollowingAccounts implements GetFollowingAccountsInterface
{
    public function execute(GetFollowingAccountsInputPort $input): GetFollowingAccountsOutputPort
    {
        $accounts = DB::table('follows')
            ->join('accounts', 'follows.followed_account_identifier', '=', 'accounts.account_identifier')
            ->where('follows.following_account_identifier', $input->accountIdentifier())
            ->orderBy('follows.created_at')
            ->orderBy('follows.followed_account_identifier')
            ->get(['accounts.account_identifier', 'accounts.account_name', 'accounts.account_bio'])
            ->map(static fn (object $account): array => ['accountIdentifier' => (string) $account->account_identifier, 'accountName' => (string) $account->account_name, 'accountBio' => $account->account_bio === null ? null : (string) $account->account_bio])
            ->all();

        return new GetFollowingAccountsOutput($accounts);
    }
}
