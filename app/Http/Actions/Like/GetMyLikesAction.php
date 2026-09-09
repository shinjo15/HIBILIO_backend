<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\GetMyLikesRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class GetMyLikesAction
{
    public function __construct(
        private GetMyLikesInterface $getMyLikes,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetMyLikesRequest $request): JsonResponse
    {
        try {
            $result = $this->getMyLikes->execute($request->toInput($this->authService->accountIdentifier()));

            return new JsonResponse([
                'likes' => array_map(static fn (array $like): array => [
                    'post_identifier' => $like['postIdentifier'],
                    'routine_identifier' => $like['routineIdentifier'],
                    'post_category' => $like['postCategory'],
                    'post_like_count' => $like['postLikeCount'],
                    'post_support_count' => $like['postSupportCount'],
                    'liked_at' => $like['likedAt'],
                ], $result->items()),
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
