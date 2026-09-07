<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\GetCustomizedRoutinesRequest;
use Illuminate\Http\JsonResponse;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInterface;

final readonly class GetCustomizedRoutinesAction
{
    public function __construct(
        private GetCustomizedRoutinesInterface $getCustomizedRoutines,
    ) {}

    public function __invoke(GetCustomizedRoutinesRequest $request): JsonResponse
    {
        $result = $this->getCustomizedRoutines->execute($request->toInput());

        if (! $result->parentRoutineExists()) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'items' => array_map(static fn (array $routine): array => [
                'account_identifier' => $routine['accountIdentifier'],
                'account_name' => $routine['accountName'],
                'routine_name' => $routine['routineName'],
                'routine_memo' => $routine['routineMemo'],
                'routine_execution_minutes' => $routine['routineExecutionMinutes'],
                'execution_count' => $routine['executionCount'],
                'customization_count' => $routine['customizationCount'],
                'like_count' => $routine['likeCount'],
            ], $result->items()),
            'total' => $result->total(),
        ]);
    }
}
