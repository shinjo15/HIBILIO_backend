<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetMyRoutineExecutionsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyRoutineExecutionsAction
{
    public function __construct(private GetAccountRoutineExecutionsInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyRoutineExecutionsRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier());
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }
        $result = $this->query->execute($input);

        return new JsonResponse(['items' => $result->items(), 'total' => $result->total()]);
    }
}
