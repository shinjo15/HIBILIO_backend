<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use Illuminate\Http\JsonResponse;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInput;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyRoutineExecutionDraftsAction
{
    public function __construct(private GetMyRoutineExecutionDraftsInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(): JsonResponse
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new JsonResponse([], 401);
        }

        $result = $this->query->execute(new GetMyRoutineExecutionDraftsInput($accountIdentifier));

        return new JsonResponse(['items' => array_map(static fn (array $item): array => [
            'routine_execution_identifier' => $item['routineExecutionIdentifier'],
            'routine_identifier' => $item['routineIdentifier'],
            'executed_at' => $item['executedAt'],
            'routine_execution_memo' => $item['routineExecutionMemo'],
            'executed_routine_action_identifiers' => $item['executedRoutineActionIdentifiers'],
        ], $result->items()), 'total' => $result->total()]);
    }
}
