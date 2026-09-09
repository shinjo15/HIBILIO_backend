<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\RemoveLikeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInterface;
use Src\Like\Domain\Exception\NotLikedException;
use Src\Like\Domain\Exception\PostNotFoundForLikeException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RemoveLikeAction
{
    public function __construct(private RemoveLikeInterface $removeLike, private AuthServiceInterface $authService) {}

    public function __invoke(RemoveLikeRequest $request): Response|JsonResponse
    {
        try {
            $this->removeLike->execute($request->toInput($this->authService->accountIdentifier()));
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (PostNotFoundForLikeException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (NotLikedException) {
            return new Response('', 204);
        }

        return new Response('', 204);
    }
}
