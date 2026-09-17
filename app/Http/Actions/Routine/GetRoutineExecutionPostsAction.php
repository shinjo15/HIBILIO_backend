<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\GetRoutineExecutionPostsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Application\Service\BlockVisibilityServiceInterface;

final readonly class GetRoutineExecutionPostsAction
{
    public function __construct(
        private GetRoutineExecutionPostsInterface $getRoutineExecutionPosts,
        private AuthServiceInterface $authService,
        private BlockVisibilityServiceInterface $blockVisibilityService,
    ) {}

    public function __invoke(GetRoutineExecutionPostsRequest $request): JsonResponse
    {
        try {
            $viewerAccountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $viewerAccountIdentifier = null;
        }

        if (! $this->blockVisibilityService->routineIsVisible($viewerAccountIdentifier, (string) $request->route('routine_identifier'))) {
            return new JsonResponse([], 404);
        }

        $result = $this->getRoutineExecutionPosts->execute($request->toInput($viewerAccountIdentifier));

        return new JsonResponse([
            'items' => array_map(static fn (array $post): array => [
                'account_identifier' => $post['accountIdentifier'],
                'account_name' => $post['accountName'],
                'icon_image_url' => $post['iconImageUrl'],
                'executed_action_count' => $post['executedActionCount'],
                'posted_at' => $post['postedAt'],
                'routine_execution_identifier' => $post['routineExecutionIdentifier'],
                'routine_execution_memo' => $post['routineExecutionMemo'],
                'support_count' => $post['supportCount'],
            ], $result->items()),
            'total' => $result->total(),
        ]);
    }
}
