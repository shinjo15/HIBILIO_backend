<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Repository;

use App\Models\AccountModel;
use Src\Like\Domain\Entity\LikeNotificationSetting;
use Src\Like\Domain\Repository\LikeNotificationSettingRepositoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationEnabled;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LikeNotificationSettingRepository implements LikeNotificationSettingRepositoryInterface
{
    public function find(AccountIdentifier $account): ?LikeNotificationSetting
    {
        $model = AccountModel::query()->where('account_identifier', $account->value())->where('available', true)->where('status', 'active')->lockForUpdate()->first();

        return $model === null ? null : new LikeNotificationSetting($account, new LikeNotificationEnabled((bool) $model->like_notifications_enabled));
    }

    public function save(LikeNotificationSetting $setting): void
    {
        AccountModel::query()->where('account_identifier', $setting->accountIdentifier()->value())->update(['like_notifications_enabled' => $setting->enabled()->value()]);
    }
}
