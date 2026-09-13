<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\ChangeAccountUiModeRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\ChangeAccountUiMode\ChangeAccountUiModeInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class ChangeAccountUiModeAction
{
    public function __construct(private ChangeAccountUiModeInterface $changeAccountUiMode, private AuthServiceInterface $authService) {}

    public function __invoke(ChangeAccountUiModeRequest $request): Response
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            return new Response('', 401);
        }
        try {
            $this->changeAccountUiMode->execute($request->toInput($accountIdentifier));
        } catch (AccountNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
