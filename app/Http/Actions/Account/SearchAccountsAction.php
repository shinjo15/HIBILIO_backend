<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\SearchAccountsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class SearchAccountsAction
{
    public function __construct(
        private SearchAccountsInterface $searchAccounts,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(SearchAccountsRequest $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $accountIdentifier = null;
        }

        $output = $this->searchAccounts->execute($request->toInput($accountIdentifier));

        return new JsonResponse([
            'accounts' => array_map(static fn (array $account): array => [
                'account_identifier' => $account['accountIdentifier'],
                'account_name' => $account['accountName'],
                'account_bio' => $account['accountBio'],
                'icon_image_url' => $account['iconImageUrl'],
            ], $output->accounts()),
            'total' => $output->total(),
        ]);
    }
}
