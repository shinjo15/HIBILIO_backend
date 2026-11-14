<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\RegisterListeningContentRequest;
use Illuminate\Http\Response;
use Src\Account\Application\Usecase\Command\RegisterListeningContent\RegisterListeningContentInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RegisterListeningContentAction
{
    public function __construct(
        private RegisterListeningContentInterface $registerListeningContent,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(RegisterListeningContentRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        $this->registerListeningContent->execute($request->toInput($accountIdentifier));

        return new Response('', 204);
    }
}
