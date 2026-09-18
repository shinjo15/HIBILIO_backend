<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInput;
use Src\Account\Application\Usecase\Query\GetFollowingAccounts\GetFollowingAccountsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyFollowingAccountsAction
{
    public function __construct(private GetFollowingAccountsInterface $getFollowingAccounts, private AuthServiceInterface $authService) {}

    public function __invoke(): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
            $output = $this->getFollowingAccounts->execute(new GetFollowingAccountsInput($accountIdentifier, $accountIdentifier));
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(['following_accounts' => array_map(static fn (array $account): array => ['account_identifier' => $account['accountIdentifier'], 'account_name' => $account['accountName'], 'account_bio' => $account['accountBio'], 'icon_image_url' => $account['iconImageUrl']], $output->accounts())]);
    }
}
