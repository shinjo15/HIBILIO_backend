<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetRecentExecutedRoutinesRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines\GetRecentExecutedRoutinesInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetRecentExecutedRoutinesAction
{
    public function __construct(private GetRecentExecutedRoutinesInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(GetRecentExecutedRoutinesRequest $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse($this->query->execute($accountIdentifier, $request->currentPage(), $request->perPage()));
    }
}
