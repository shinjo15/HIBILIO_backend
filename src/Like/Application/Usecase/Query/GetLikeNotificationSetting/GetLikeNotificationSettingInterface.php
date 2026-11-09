<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikeNotificationSetting;

interface GetLikeNotificationSettingInterface
{
    public function execute(GetLikeNotificationSettingInputPort $input): GetLikeNotificationSettingOutputPort;
}
