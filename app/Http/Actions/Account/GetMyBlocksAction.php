<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetMyBlocksRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyBlocksAction
{
    public function __construct(private GetMyBlocksInterface $getMyBlocks, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyBlocksRequest $request): JsonResponse
    {
        try {
            $blocks = $this->getMyBlocks->execute($request->toInput($this->authService->accountIdentifier()));
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(['blocks' => array_map(static fn (array $account): array => ['account_identifier' => $account['accountIdentifier'], 'account_name' => $account['accountName']], $blocks)]);
    }
}
