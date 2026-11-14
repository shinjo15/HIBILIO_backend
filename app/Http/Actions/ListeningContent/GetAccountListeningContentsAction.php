<?php

declare(strict_types=1);

namespace App\Http\Actions\ListeningContent;

use Illuminate\Http\JsonResponse;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInput;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;

final readonly class GetAccountListeningContentsAction
{
    public function __construct(private GetAccountListeningContentsInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(string $account_identifier): JsonResponse
    {
        try {
            $output = $this->query->execute(new GetAccountListeningContentsInput($account_identifier, $this->authService->accountIdentifier()));
        } catch (BlockedAccountVisibilityException) {
            return new JsonResponse([], 404);
        }

        if ($output === null) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse(['items' => $output->items()]);
    }
}
