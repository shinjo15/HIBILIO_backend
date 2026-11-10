<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetRecentExecutedRoutinesRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentlyExecutedRoutines\GetRecentlyExecutedRoutinesInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetRecentExecutedRoutinesAction
{
    public function __construct(private GetRecentlyExecutedRoutinesInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(GetRecentExecutedRoutinesRequest $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        $result = $this->query->execute($request->toInput($accountIdentifier));

        return new JsonResponse(['items' => array_map(static fn (array $routine): array => [
            'post_identifier' => $routine['postIdentifier'], 'routine_identifier' => $routine['routineIdentifier'],
            'customization_origin' => $routine['customizationOrigin'] === null ? null : [
                'routine_identifier' => $routine['customizationOrigin']['routineIdentifier'],
                'routine_name' => $routine['customizationOrigin']['routineName'],
                'account_name' => $routine['customizationOrigin']['accountName'],
                'icon_image_url' => $routine['customizationOrigin']['iconImageUrl'],
            ],
            'account_identifier' => $routine['accountIdentifier'], 'account_name' => $routine['accountName'],
            'icon_image_url' => $routine['iconImageUrl'], 'posted_at' => $routine['postedAt'],
            'routine_name' => $routine['routineName'], 'routine_execution_minutes' => $routine['routineExecutionMinutes'],
            'tags' => array_map(static fn (array $tag): array => ['tag_identifier' => $tag['tagIdentifier'], 'tag_name' => $tag['tagName']], $routine['tags']),
            'routine_actions' => array_map(static fn (array $action): array => ['routine_action_identifier' => $action['routineActionIdentifier'], 'action_name' => $action['actionName'], 'action_minutes' => $action['actionMinutes']], $routine['routineActions']),
            'post_like_count' => $routine['postLikeCount'], 'post_support_count' => $routine['postSupportCount'],
            'liked' => $routine['liked'], 'execution_count' => $routine['executionCount'],
            'customization_count' => $routine['customizationCount'], 'my_execution_count' => $routine['myExecutionCount'],
            'last_executed_at' => $routine['lastExecutedAt'],
        ], $result->items()), 'total' => $result->total()]);
    }
}
