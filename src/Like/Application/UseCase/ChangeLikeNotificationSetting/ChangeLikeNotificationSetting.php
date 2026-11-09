<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\ChangeLikeNotificationSetting;

use Src\Like\Domain\Repository\LikeNotificationSettingRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class ChangeLikeNotificationSetting implements ChangeLikeNotificationSettingInterface
{
    public function __construct(private LikeNotificationSettingRepositoryInterface $settings, private TransactionManagerInterface $transactionManager) {}

    public function execute(ChangeLikeNotificationSettingInputPort $input): bool
    {
        return $this->transactionManager->transaction(function () use ($input): bool {
            $setting = $this->settings->find($input->accountIdentifier());
            if ($setting === null) {
                return false;
            }
            $setting->change($input->enabled());
            $this->settings->save($setting);

            return true;
        });
    }
}
