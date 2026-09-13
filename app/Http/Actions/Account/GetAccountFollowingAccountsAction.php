<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use Illuminate\Http\JsonResponse;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInput;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInterface;

final readonly class GetAccountFollowingAccountsAction
{
    public function __construct(private GetFollowingAccountsInterface $getFollowingAccounts) {}

    public function __invoke(string $account_identifier): JsonResponse
    {
        $output = $this->getFollowingAccounts->execute(new GetFollowingAccountsInput($account_identifier));

        return new JsonResponse(['following_accounts' => array_map(static fn (array $account): array => ['account_identifier' => $account['accountIdentifier'], 'account_name' => $account['accountName'], 'account_bio' => $account['accountBio']], $output->accounts())]);
    }
}
