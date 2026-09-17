<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInput;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Application\Service\BlockVisibilityServiceInterface;

final readonly class GetAccountFollowingAccountsAction
{
    public function __construct(private GetFollowingAccountsInterface $getFollowingAccounts, private AuthServiceInterface $authService, private BlockVisibilityServiceInterface $blockVisibilityService) {}

    public function __invoke(string $account_identifier): JsonResponse
    {
        try {
            $viewerAccountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $viewerAccountIdentifier = null;
        }

        if (! $this->blockVisibilityService->accountIsVisible($viewerAccountIdentifier, $account_identifier)) {
            return new JsonResponse([], 404);
        }

        $output = $this->getFollowingAccounts->execute(new GetFollowingAccountsInput($account_identifier, $viewerAccountIdentifier));

        return new JsonResponse(['following_accounts' => array_map(static fn (array $account): array => ['account_identifier' => $account['accountIdentifier'], 'account_name' => $account['accountName'], 'account_bio' => $account['accountBio'], 'icon_image_url' => $account['iconImageUrl']], $output->accounts())]);
    }
}
