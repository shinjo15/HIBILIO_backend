<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikeNotificationSetting;

interface GetLikeNotificationSettingInputPort
{
    public function accountIdentifier(): string;
}
