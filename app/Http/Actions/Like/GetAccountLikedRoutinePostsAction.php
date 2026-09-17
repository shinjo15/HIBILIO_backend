<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\GetAccountLikedRoutinePostsRequest;
use Illuminate\Http\JsonResponse;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;

final readonly class GetAccountLikedRoutinePostsAction
{
    public function __construct(
        private GetLikedRoutinePostsInterface $getLikedRoutinePosts,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetAccountLikedRoutinePostsRequest $request): JsonResponse
    {
        $viewerAccountIdentifier = $this->authService->accountIdentifier();

        try {
            $result = $this->getLikedRoutinePosts->execute($request->toInput($viewerAccountIdentifier));
        } catch (BlockedAccountVisibilityException) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'items' => array_map(static fn (array $item): array => [
                'post_identifier' => $item['postIdentifier'],
                'routine_identifier' => $item['routineIdentifier'],
                'account_identifier' => $item['accountIdentifier'],
                'account_name' => $item['accountName'],
                'icon_image_url' => $item['iconImageUrl'],
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
                'liked' => $item['liked'],
                'execution_count' => $item['executionCount'],
                'customization_count' => $item['customizationCount'],
                'liked_at' => $item['likedAt'],
            ], $result->items()),
            'total' => $result->total(),
        ]);
    }
}
