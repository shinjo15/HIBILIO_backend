<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\GetAccountLikedRoutinePostsRequest;
use Illuminate\Http\JsonResponse;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInterface;

final readonly class GetAccountLikedRoutinePostsAction
{
    public function __construct(
        private GetLikedRoutinePostsInterface $getLikedRoutinePosts,
    ) {}

    public function __invoke(GetAccountLikedRoutinePostsRequest $request): JsonResponse
    {
        $result = $this->getLikedRoutinePosts->execute($request->toInput());

        return new JsonResponse([
            'items' => array_map(static fn (array $item): array => [
                'post_identifier' => $item['postIdentifier'],
                'routine_identifier' => $item['routineIdentifier'],
                'account_identifier' => $item['accountIdentifier'],
                'account_name' => $item['accountName'],
                'posted_at' => $item['postedAt'],
                'routine_name' => $item['routineName'],
                'routine_execution_minutes' => $item['routineExecutionMinutes'],
                'tags' => array_map(static fn (array $tag): array => [
                    'tag_identifier' => $tag['tagIdentifier'],
                    'tag_name' => $tag['tagName'],
                ], $item['tags']),
                'routine_actions' => array_map(static fn (array $action): array => [
                    'routine_action_identifier' => $action['routineActionIdentifier'],
                    'action_name' => $action['actionName'],
                    'action_minutes' => $action['actionMinutes'],
                ], $item['routineActions']),
                'post_like_count' => $item['postLikeCount'],
                'post_support_count' => $item['postSupportCount'],
                'execution_count' => $item['executionCount'],
                'customization_count' => $item['customizationCount'],
                'liked_at' => $item['likedAt'],
            ], $result->items()),
            'total' => $result->total(),
        ]);
    }
}
