<?php

declare(strict_types=1);

namespace Src\Support\Domain\Repository;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Domain\Entity\SupportNotification;
use Src\Support\Domain\ValueObject\SupportNotificationTarget;

interface SupportNotificationRepositoryInterface
{
    public function findTarget(AccountIdentifier $actor, PostIdentifier $post): ?SupportNotificationTarget;

    public function save(SupportNotification $notification): void;
}
