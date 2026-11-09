<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Factory;

use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\Factory\LikeNotificationFactoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationContent;
use Src\Like\Domain\ValueObject\LikeNotificationIdentifier;
use Src\Like\Domain\ValueObject\LikeNotificationStatus;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LikeNotificationFactory implements LikeNotificationFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountIdentifier $recipient, string $actorName, string $routineName): LikeNotification
    {
        return new LikeNotification(
            new LikeNotificationIdentifier($this->uuidService->generate()),
            $recipient,
            new LikeNotificationContent($actorName.'さんが「'.$routineName.'」にいいねしました。'),
            LikeNotificationStatus::Unread,
        );
    }
}
