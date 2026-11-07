<?php

declare(strict_types=1);

namespace App\Http\Actions\Notification;

use App\Http\Requests\Notification\GetMyNotificationsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class GetMyNotificationsAction
{
    public function __construct(private GetMyNotificationsInterface $getMyNotifications, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyNotificationsRequest $request): JsonResponse
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        try {
            $result = $this->getMyNotifications->execute($request->toInput($accountIdentifier));
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse([], 500);
        }

        return new JsonResponse([
            'notifications' => array_map(static fn (array $notification): array => [
                'notification_identifier' => $notification['notificationIdentifier'],
                'category' => $notification['category'],
                'status' => $notification['status'],
                'title' => $notification['title'],
                'content' => $notification['content'],
                'created_at' => $notification['createdAt'],
            ], $result->notifications()),
            'total' => $result->total(),
        ]);
    }
}
