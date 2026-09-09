<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetMyRoutinePostsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyRoutinePostsAction
{
    public function __construct(private GetAccountRoutinePostsInterface $getAccountRoutinePosts, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyRoutinePostsRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier());
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }
        $result = $this->getAccountRoutinePosts->execute($input);

        return new JsonResponse(['items' => array_map(static fn (array $post): array => [
            'post_identifier' => $post['postIdentifier'], 'routine_identifier' => $post['routineIdentifier'],
            'account_identifier' => $post['accountIdentifier'], 'account_name' => $post['accountName'],
            'posted_at' => $post['postedAt'], 'routine_name' => $post['routineName'],
            'routine_execution_minutes' => $post['routineExecutionMinutes'],
            'tags' => array_map(static fn (array $tag): array => ['tag_identifier' => $tag['tagIdentifier'], 'tag_name' => $tag['tagName']], $post['tags']),
            'routine_actions' => array_map(static fn (array $action): array => ['routine_action_identifier' => $action['routineActionIdentifier'], 'action_name' => $action['actionName'], 'action_minutes' => $action['actionMinutes']], $post['routineActions']),
            'post_like_count' => $post['postLikeCount'], 'post_support_count' => $post['postSupportCount'],
            'execution_count' => $post['executionCount'], 'customization_count' => $post['customizationCount'],
        ], $result->items()), 'total' => $result->total()]);
    }
}
