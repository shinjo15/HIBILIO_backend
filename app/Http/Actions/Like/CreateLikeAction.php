<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\CreateLikeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Like\Application\UseCase\CreateLike\CreateLikeInterface;
use Src\Like\Domain\Exception\AlreadyLikedException;
use Src\Like\Domain\Exception\PostNotFoundForLikeException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateLikeAction
{
    public function __construct(private CreateLikeInterface $createLike, private AuthServiceInterface $authService) {}

    public function __invoke(CreateLikeRequest $request): Response|JsonResponse
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->createLike->execute($request->toInput($accountIdentifier));

            return new Response('', 204);
        } catch (PostNotFoundForLikeException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (AlreadyLikedException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 409);
        }
    }
}
