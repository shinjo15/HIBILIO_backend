<?php

declare(strict_types=1);

namespace Src\Like\Domain\Repository;

use Src\Like\Domain\Entity\LikeNotificationSetting;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface LikeNotificationSettingRepositoryInterface
{
    public function find(AccountIdentifier $account): ?LikeNotificationSetting;

    public function save(LikeNotificationSetting $setting): void;
}
