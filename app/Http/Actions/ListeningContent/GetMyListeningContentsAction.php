<?php

declare(strict_types=1);

namespace App\Http\Actions\ListeningContent;

use Illuminate\Http\JsonResponse;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInput;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyListeningContentsAction
{
    public function __construct(private GetAccountListeningContentsInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(): JsonResponse
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new JsonResponse([], 401);
        }

        $output = $this->query->execute(new GetAccountListeningContentsInput($accountIdentifier, $accountIdentifier));

        return $output === null ? new JsonResponse([], 404) : new JsonResponse(['items' => $output->items()]);
    }
}
