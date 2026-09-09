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

        return new JsonResponse(LikedRoutinePostsResponse::from($result));
    }
}
