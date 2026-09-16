<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetMyBlocks;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInputPort;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInterface;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksOutput;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class GetMyBlocks implements GetMyBlocksInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetMyBlocksInputPort $input): GetMyBlocksOutputPort
    {
        $blocks = DB::table('blocks')
            ->join('accounts', 'blocks.blocked_account_identifier', '=', 'accounts.account_identifier')
            ->where('blocks.blocking_account_identifier', $input->accountIdentifier())
            ->orderBy('blocks.created_at')
            ->orderBy('blocks.blocked_account_identifier')
            ->get(['accounts.account_identifier', 'accounts.account_name', 'accounts.account_bio'])
            ->map(fn (object $account): array => ['accountIdentifier' => (string) $account->account_identifier, 'accountName' => (string) $account->account_name, 'accountBio' => $account->account_bio === null ? null : (string) $account->account_bio, 'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier))])
            ->all();

        return new GetMyBlocksOutput($blocks);
    }
}
