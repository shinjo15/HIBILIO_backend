<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\GetMyLikesRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyLikesAction
{
    public function __construct(
        private GetLikedRoutinePostsInterface $getLikedRoutinePosts,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetMyLikesRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier());
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(LikedRoutinePostsResponse::from(
            $this->getLikedRoutinePosts->execute($input),
        ));
    }
}
