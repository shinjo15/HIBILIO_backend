<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\GetRoutineDetailsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetRoutineDetailsAction
{
    public function __construct(
        private GetRoutineDetailsInterface $getRoutineDetails,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetRoutineDetailsRequest $request): JsonResponse
    {
        try {
            $viewerAccountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $viewerAccountIdentifier = null;
        }

        $routineDetails = $this->getRoutineDetails->execute($request->toInput($viewerAccountIdentifier))->routineDetails();

        if ($routineDetails === null) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'account_identifier' => $routineDetails['accountIdentifier'],
            'account_name' => $routineDetails['accountName'],
            'icon_image_url' => $routineDetails['iconImageUrl'],
            'routine_name' => $routineDetails['routineName'],
            'routine_memo' => $routineDetails['routineMemo'],
            'routine_execution_minutes' => $routineDetails['routineExecutionMinutes'],
            'execution_count' => $routineDetails['executionCount'],
            'customization_count' => $routineDetails['customizationCount'],
            'like_count' => $routineDetails['likeCount'],
            'routine_actions' => array_map(static fn (array $action): array => [
                'routine_action_identifier' => $action['routineActionIdentifier'],
                'action_name' => $action['actionName'],
                'action_memo' => $action['actionMemo'],
                'action_minutes' => $action['actionMinutes'],
            ], $routineDetails['routineActions']),
        ]);
    }
}
