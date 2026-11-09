<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Query\GetLikeNotificationSetting;

use App\Models\AccountModel;
use Src\Like\Application\Usecase\Query\GetLikeNotificationSetting\GetLikeNotificationSettingInputPort;
use Src\Like\Application\Usecase\Query\GetLikeNotificationSetting\GetLikeNotificationSettingInterface;
use Src\Like\Application\Usecase\Query\GetLikeNotificationSetting\GetLikeNotificationSettingOutput;

final class GetLikeNotificationSetting implements GetLikeNotificationSettingInterface
{
    public function execute(GetLikeNotificationSettingInputPort $input): GetLikeNotificationSettingOutput
    {
        $value = AccountModel::query()->where('account_identifier', $input->accountIdentifier())->where('available', true)->where('status', 'active')->value('like_notifications_enabled');

        return new GetLikeNotificationSettingOutput($value === null ? null : (bool) $value);
    }
}
