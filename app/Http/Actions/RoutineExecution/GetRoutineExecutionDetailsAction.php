<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetRoutineExecutionDetailsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetRoutineExecutionDetailsAction
{
    public function __construct(
        private GetRoutineExecutionDetailsInterface $getRoutineExecutionDetails,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetRoutineExecutionDetailsRequest $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $accountIdentifier = null;
        }

        $details = $this->getRoutineExecutionDetails->execute($request->toInput($accountIdentifier))->routineExecutionDetails();

        if ($details === null) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'routine_execution_identifier' => $details['routineExecutionIdentifier'],
            'routine_identifier' => $details['routineIdentifier'],
            'routine_name' => $details['routineName'],
            'routine_memo' => $details['routineMemo'],
            'account_identifier' => $details['accountIdentifier'],
            'account_name' => $details['accountName'],
            'icon_image_url' => $details['iconImageUrl'],
            'routine_execution_memo' => $details['routineExecutionMemo'],
            'executed_at' => $details['executedAt'],
            'posted_at' => $details['postedAt'],
            'support_count' => $details['supportCount'],
            'tags' => array_map(static fn (array $tag): array => [
                'tag_identifier' => $tag['tagIdentifier'],
                'tag_name' => $tag['tagName'],
            ], $details['tags']),
            'routine_execution_actions' => array_map(static fn (array $action): array => [
                'routine_action_identifier' => $action['routineActionIdentifier'],
                'action_name' => $action['actionName'],
                'action_memo' => $action['actionMemo'],
                'action_minutes' => $action['actionMinutes'],
            ], $details['routineExecutionActions']),
        ]);
    }
}
