<?php

declare(strict_types=1);

namespace App\Http\Actions\Notification;

use App\Http\Requests\Notification\MarkPersonalNotificationAsReadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead\MarkPersonalNotificationAsReadInterface;
use Src\Notification\Domain\Exception\PersonalNotificationAccessDeniedException;
use Src\Notification\Domain\Exception\PersonalNotificationNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class MarkPersonalNotificationAsReadAction
{
    public function __construct(private MarkPersonalNotificationAsReadInterface $markPersonalNotificationAsRead, private AuthServiceInterface $authService) {}

    public function __invoke(MarkPersonalNotificationAsReadRequest $request): Response|JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new Response('', 401);
        }

        try {
            $this->markPersonalNotificationAsRead->execute($request->toInput($accountIdentifier));
        } catch (PersonalNotificationAccessDeniedException) {
            return new Response('', 403);
        } catch (PersonalNotificationNotFoundException) {
            return new Response('', 404);
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse([], 500);
        }

        return new Response('', 204);
    }
}
