<?php

declare(strict_types=1);

namespace App\Http\Actions\Notification;

use App\Http\Requests\Notification\GetAnnouncementsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class GetAnnouncementsAction
{
    public function __construct(private GetAnnouncementsInterface $getAnnouncements, private AuthServiceInterface $authService) {}

    public function __invoke(GetAnnouncementsRequest $request): JsonResponse
    {
        try {
            $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        try {
            $result = $this->getAnnouncements->execute($request->toInput());
        } catch (Throwable $exception) {
            report($exception);

            return new JsonResponse([], 500);
        }

        return new JsonResponse([
            'announcements' => array_map(static fn (array $announcement): array => [
                'announcement_identifier' => $announcement['announcementIdentifier'],
                'title' => $announcement['title'],
                'content' => $announcement['content'],
                'created_at' => $announcement['createdAt'],
            ], $result->announcements()),
            'total' => $result->total(),
        ]);
    }
}
