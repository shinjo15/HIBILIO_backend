<?php

declare(strict_types=1);

namespace App\Http\Actions\Notification;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountInput;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class GetMyUnreadNotificationCountAction
{
    public function __construct(private GetMyUnreadNotificationCountInterface $getMyUnreadNotificationCount, private AuthServiceInterface $authService) {}

    public function __invoke(): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (QueryException $exception) {
            report($exception);

            return new JsonResponse([], 500);
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        try {
            $result = $this->getMyUnreadNotificationCount->execute(new GetMyUnreadNotificationCountInput($accountIdentifier));
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse([], 500);
        }

        return new JsonResponse(['unread_count' => $result->unreadCount()]);
    }
}
