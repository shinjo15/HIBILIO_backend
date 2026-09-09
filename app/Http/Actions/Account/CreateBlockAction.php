<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\CreateBlockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateBlockException;
use Src\Account\Domain\Exception\SelfBlockException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateBlockAction
{
    public function __construct(
        private CreateBlockInterface $createBlock,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CreateBlockRequest $request): Response|JsonResponse
    {
        try {
            $this->createBlock->execute($request->toInput($this->authService->accountIdentifier()));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (AccountNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (SelfBlockException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (DuplicateBlockException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 409);
        }
    }
}
