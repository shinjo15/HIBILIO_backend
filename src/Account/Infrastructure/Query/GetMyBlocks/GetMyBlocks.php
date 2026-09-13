<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetMyBlocks;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInput;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInterface;

final class GetMyBlocks implements GetMyBlocksInterface
{
    public function execute(GetMyBlocksInput $input): array
    {
        return DB::table('blocks')
            ->join('accounts', 'blocks.blocked_account_identifier', '=', 'accounts.account_identifier')
            ->where('blocks.blocking_account_identifier', $input->accountIdentifier())
            ->orderBy('blocks.created_at')
            ->orderBy('blocks.blocked_account_identifier')
            ->get(['accounts.account_identifier', 'accounts.account_name'])
            ->map(static fn (object $account): array => ['accountIdentifier' => (string) $account->account_identifier, 'accountName' => (string) $account->account_name])
            ->all();
    }
}
