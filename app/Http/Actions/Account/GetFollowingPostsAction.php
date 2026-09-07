<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetFollowingPostsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetFollowingPosts\GetFollowingPostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class GetFollowingPostsAction
{
    public function __construct(
        private GetFollowingPostsInterface $getFollowingPosts,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetFollowingPostsRequest $request): JsonResponse
    {
        try {
            $result = $this->getFollowingPosts->execute(
                $request->toInput($this->authService->accountIdentifier()),
            );

            return new JsonResponse([
                'posts' => array_map(static fn (array $post): array => [
                    'post_identifier' => $post['postIdentifier'],
                    'routine_identifier' => $post['routineIdentifier'],
                    'post_category' => $post['postCategory'],
                    'account_identifier' => $post['accountIdentifier'],
                    'account_name' => $post['accountName'],
                    'posted_at' => $post['postedAt'],
                    'routine_name' => $post['routineName'],
                    'routine_execution_minutes' => $post['routineExecutionMinutes'],
                    'tags' => array_map(static fn (array $tag): array => [
                        'tag_identifier' => $tag['tagIdentifier'],
                        'tag_name' => $tag['tagName'],
                    ], $post['tags']),
                    'routine_actions' => array_map(static fn (array $action): array => [
                        'routine_action_identifier' => $action['routineActionIdentifier'],
                        'action_name' => $action['actionName'],
                        'action_minutes' => $action['actionMinutes'],
                    ], $post['routineActions']),
                    'post_like_count' => $post['postLikeCount'],
                    'liked' => $post['liked'],
                    'execution_count' => $post['executionCount'],
                    'customization_count' => $post['customizationCount'],
                ], $result->posts()),
                'total' => $result->total(),
            ]);
        } catch (Throwable $exception) {
            if (! $exception instanceof RuntimeException) {
                report($exception);
            }

            return new JsonResponse([], $exception instanceof RuntimeException ? 401 : 500);
        }
    }
}
