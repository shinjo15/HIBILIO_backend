<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\ChangeAccountSupportNotificationsRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\ChangeAccountSupportNotifications\ChangeAccountSupportNotificationsInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class ChangeAccountSupportNotificationsAction
{
    public function __construct(private ChangeAccountSupportNotificationsInterface $changeAccountSupportNotifications, private AuthServiceInterface $authService) {}

    public function __invoke(ChangeAccountSupportNotificationsRequest $request): Response
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new Response('', 401);
        }
        try {
            $this->changeAccountSupportNotifications->execute($request->toInput($accountIdentifier));
        } catch (AccountNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
