<?php

declare(strict_types=1);

namespace App\Http\Actions\ListeningContent;

use App\Http\Requests\ListeningContent\CreateListeningContentRequest;
use Illuminate\Http\Response;
use Src\ListeningContent\Application\UseCase\CreateListeningContent\CreateListeningContentInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateListeningContentAction
{
    public function __construct(
        private CreateListeningContentInterface $createListeningContent,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CreateListeningContentRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        $this->createListeningContent->execute($request->toInput($accountIdentifier));

        return new Response('', 204);
    }
}
