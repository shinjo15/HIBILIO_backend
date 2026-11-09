<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\ChangeLikeNotificationSetting;

interface ChangeLikeNotificationSettingInterface
{
    public function execute(ChangeLikeNotificationSettingInputPort $input): bool;
}
