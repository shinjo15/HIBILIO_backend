<?php

declare(strict_types=1);

namespace App\Http\Actions\Support;

use App\Http\Requests\Support\GetMySupportsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInterface;
use Throwable;

use function report;

final readonly class GetMySupportsAction
{
    public function __construct(
        private GetMySupportsInterface $getMySupports,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetMySupportsRequest $request): JsonResponse
    {
        try {
            $result = $this->getMySupports->execute($request->toInput($this->authService->accountIdentifier()));

            return new JsonResponse([
                'supports' => array_map(static fn (array $support): array => [
                    'post_identifier' => $support['postIdentifier'],
                    'routine_identifier' => $support['routineIdentifier'],
                    'routine_execution_identifier' => $support['routineExecutionIdentifier'],
                    'post_category' => $support['postCategory'],
                    'post_like_count' => $support['postLikeCount'],
                    'post_support_count' => $support['postSupportCount'],
                    'liked' => $support['liked'],
                    'supported' => $support['supported'],
                    'supported_at' => $support['supportedAt'],
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
