<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Factory;

use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Support\Domain\Entity\SupportNotification;
use Src\Support\Domain\Factory\SupportNotificationFactoryInterface;
use Src\Support\Domain\ValueObject\SupportNotificationContent;
use Src\Support\Domain\ValueObject\SupportNotificationIdentifier;
use Src\Support\Domain\ValueObject\SupportNotificationStatus;

final readonly class SupportNotificationFactory implements SupportNotificationFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountIdentifier $recipient, string $actorName, string $routineName): SupportNotification
    {
        return new SupportNotification(
            new SupportNotificationIdentifier($this->uuidService->generate()),
            $recipient,
            new SupportNotificationContent($actorName.'さんが「'.$routineName.'」の実行を応援しました。'),
            SupportNotificationStatus::Unread,
        );
    }
}
