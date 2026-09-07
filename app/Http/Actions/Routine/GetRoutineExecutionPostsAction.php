<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\GetRoutineExecutionPostsRequest;
use Illuminate\Http\JsonResponse;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInterface;

final readonly class GetRoutineExecutionPostsAction
{
    public function __construct(
        private GetRoutineExecutionPostsInterface $getRoutineExecutionPosts,
    ) {}

    public function __invoke(GetRoutineExecutionPostsRequest $request): JsonResponse
    {
        $result = $this->getRoutineExecutionPosts->execute($request->toInput());

        return new JsonResponse([
            'items' => array_map(static fn (array $post): array => [
                'account_identifier' => $post['accountIdentifier'],
                'account_name' => $post['accountName'],
                'executed_action_count' => $post['executedActionCount'],
                'posted_at' => $post['postedAt'],
                'routine_execution_memo' => $post['routineExecutionMemo'],
                'support_count' => $post['supportCount'],
            ], $result->items()),
            'total' => $result->total(),
        ]);
    }
}
