<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\CreateFollowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateFollowException;
use Src\Account\Domain\Exception\SelfFollowException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateFollowAction
{
    public function __construct(
        private CreateFollowInterface $createFollow,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CreateFollowRequest $request): Response|JsonResponse
    {
        try {
            $this->createFollow->execute($request->toInput($this->authService->accountIdentifier()));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (AccountNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (SelfFollowException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (DuplicateFollowException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 409);
        }
    }
}
