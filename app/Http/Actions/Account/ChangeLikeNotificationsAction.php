<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\ChangeLikeNotificationsRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\ChangeLikeNotifications\ChangeLikeNotificationsInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class ChangeLikeNotificationsAction
{
    public function __construct(private ChangeLikeNotificationsInterface $changeLikeNotifications, private AuthServiceInterface $authService) {}

    public function __invoke(ChangeLikeNotificationsRequest $request): Response
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new Response('', 401);
        }
        try {
            $this->changeLikeNotifications->execute($request->toInput($accountIdentifier));
        } catch (AccountNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
