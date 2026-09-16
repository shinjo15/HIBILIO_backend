<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\SearchAccounts;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsInputPort;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsInterface;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsOutput;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class SearchAccounts implements SearchAccountsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(SearchAccountsInputPort $input): SearchAccountsOutputPort
    {
        $query = DB::table('accounts')
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->orderBy('accounts.account_name')
            ->orderBy('accounts.account_identifier');

        if ($input->accountName() !== null) {
            $query->where('accounts.account_name', 'like', $input->accountName().'%');
        }

        foreach ($input->tagIdentifiers() as $tagIdentifier) {
            $query->whereExists(static function ($query) use ($tagIdentifier): void {
                $query->selectRaw('1')
                    ->from('favorite_tags')
                    ->whereColumn('favorite_tags.account_identifier', 'accounts.account_identifier')
                    ->where('favorite_tags.tag_identifier', $tagIdentifier);
            });
        }

        if ($input->accountIdentifier() !== null) {
            $query->whereNotExists($this->blockExists($input->accountIdentifier()));
        }

        $paginator = $query->paginate($input->numberOfItemsPerPage(), [
            'accounts.account_identifier',
            'accounts.account_name',
            'accounts.account_bio',
        ], 'page', $input->page());

        $accounts = $paginator->getCollection()
            ->map(fn (object $account): array => [
                'accountIdentifier' => (string) $account->account_identifier,
                'accountName' => (string) $account->account_name,
                'accountBio' => $account->account_bio === null ? null : (string) $account->account_bio,
                'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier)),
            ])
            ->values()
            ->all();

        return new SearchAccountsOutput($accounts, $paginator->total());
    }

    private function blockExists(string $accountIdentifier): \Closure
    {
        return static function ($query) use ($accountIdentifier): void {
            $query->selectRaw('1')
                ->from('blocks')
                ->where(static function ($query) use ($accountIdentifier): void {
                    $query->where('blocks.blocking_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocked_account_identifier', 'accounts.account_identifier');
                })
                ->orWhere(static function ($query) use ($accountIdentifier): void {
                    $query->where('blocks.blocked_account_identifier', $accountIdentifier)
                        ->whereColumn('blocks.blocking_account_identifier', 'accounts.account_identifier');
                });
        };
    }
}
