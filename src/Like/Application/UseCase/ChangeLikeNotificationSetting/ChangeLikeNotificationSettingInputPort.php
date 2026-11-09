<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\ChangeLikeNotificationSetting;

use Src\Like\Domain\ValueObject\LikeNotificationEnabled;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ChangeLikeNotificationSettingInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function enabled(): LikeNotificationEnabled;
}
