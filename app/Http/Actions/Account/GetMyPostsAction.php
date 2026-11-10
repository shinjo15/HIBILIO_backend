<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetMyPostsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetAccountPosts\GetAccountPostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyPostsAction
{
    public function __construct(private GetAccountPostsInterface $getMyPosts, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyPostsRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException);
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }
        $result = $this->getMyPosts->execute($input);

        return new JsonResponse(['items' => array_map(static fn (array $post): array => [
            'post_identifier' => $post['postIdentifier'],
            'routine_identifier' => $post['routineIdentifier'],
            'routine_execution_identifier' => $post['routineExecutionIdentifier'],
            'post_category' => $post['postCategory'],
            'account_identifier' => $post['accountIdentifier'],
            'account_name' => $post['accountName'],
            'icon_image_url' => $post['iconImageUrl'],
            'posted_at' => $post['postedAt'],
            'routine_name' => $post['routineName'],
            'routine_execution_minutes' => $post['routineExecutionMinutes'],
            'tags' => array_map(static fn (array $tag): array => ['tag_identifier' => $tag['tagIdentifier'], 'tag_name' => $tag['tagName']], $post['tags']),
            'routine_actions' => array_map(static fn (array $action): array => ['routine_action_identifier' => $action['routineActionIdentifier'], 'action_name' => $action['actionName'], 'action_minutes' => $action['actionMinutes']], $post['routineActions']),
            'post_like_count' => $post['postLikeCount'],
            ...($post['postCategory'] === 'routine' ? [
                'liked' => $post['liked'],
                'customization_origin' => $post['customizationOrigin'] === null ? null : [
                    'routine_identifier' => $post['customizationOrigin']['routineIdentifier'],
                    'routine_name' => $post['customizationOrigin']['routineName'],
                    'account_name' => $post['customizationOrigin']['accountName'],
                    'icon_image_url' => $post['customizationOrigin']['iconImageUrl'],
                ],
            ] : [
                'supported' => $post['supported'],
                'support_count' => $post['supportCount'],
                'executed_action_count' => $post['executedActionCount'],
                'routine_execution_memo' => $post['routineExecutionMemo'],
            ]),
            'execution_count' => $post['executionCount'],
            'customization_count' => $post['customizationCount'],
        ], $result->items()), 'total' => $result->total()]);
    }
}
