<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\RemoveBlockRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlockInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RemoveBlockAction
{
    public function __construct(private RemoveBlockInterface $removeBlock, private AuthServiceInterface $authService) {}

    public function __invoke(RemoveBlockRequest $request): Response
    {
        try {
            $this->removeBlock->execute($request->toInput($this->authService->accountIdentifier()));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        }
    }
}
