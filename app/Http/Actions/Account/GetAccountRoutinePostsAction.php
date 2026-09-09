<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetAccountRoutinePostsRequest;
use Illuminate\Http\JsonResponse;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInterface;

final readonly class GetAccountRoutinePostsAction
{
    public function __construct(private GetAccountRoutinePostsInterface $getAccountRoutinePosts) {}

    public function __invoke(GetAccountRoutinePostsRequest $request): JsonResponse
    {
        $result = $this->getAccountRoutinePosts->execute($request->toInput());

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
