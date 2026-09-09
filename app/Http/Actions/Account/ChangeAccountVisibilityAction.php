<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\ChangeAccountVisibilityRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\UseCase\ChangeAccountVisibility\ChangeAccountVisibilityInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class ChangeAccountVisibilityAction
{
    public function __construct(private ChangeAccountVisibilityInterface $changeAccountVisibility, private AuthServiceInterface $authService) {}

    public function __invoke(ChangeAccountVisibilityRequest $request): Response
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            return new Response('', 401);
        }
        try {
            $this->changeAccountVisibility->execute($request->toInput($accountIdentifier));
        } catch (AccountNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
