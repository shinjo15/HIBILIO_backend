<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikeNotificationSetting;

interface GetLikeNotificationSettingOutputPort
{
    public function enabled(): ?bool;
}
