<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Factory;

use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\Factory\LikeNotificationFactoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationContent;
use Src\Like\Domain\ValueObject\LikeNotificationIdentifier;
use Src\Like\Domain\ValueObject\LikeNotificationStatus;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Shared\Application\Service\UuidServiceInterface;

final readonly class LikeNotificationFactory implements LikeNotificationFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(LikeNotificationTarget $target): LikeNotification
    {
        return new LikeNotification(
            new LikeNotificationIdentifier($this->uuidService->generate()),
            $target->recipient(),
            new LikeNotificationContent($target->actorName().'さんが「'.$target->routineName().'」にいいねしました。'),
            LikeNotificationStatus::Unread,
        );
    }
}
