<?php

declare(strict_types=1);

namespace Src\Like\Domain\Entity;

use Src\Like\Domain\ValueObject\LikeNotificationEnabled;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LikeNotificationSetting
{
    public function __construct(private readonly AccountIdentifier $accountIdentifier, private LikeNotificationEnabled $enabled) {}

    public function change(LikeNotificationEnabled $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function enabled(): LikeNotificationEnabled
    {
        return $this->enabled;
    }
}
