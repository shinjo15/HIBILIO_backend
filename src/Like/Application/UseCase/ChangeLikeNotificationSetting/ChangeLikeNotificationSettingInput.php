<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\ChangeLikeNotificationSetting;

use Src\Like\Domain\ValueObject\LikeNotificationEnabled;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeLikeNotificationSettingInput implements ChangeLikeNotificationSettingInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private LikeNotificationEnabled $enabled) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function enabled(): LikeNotificationEnabled
    {
        return $this->enabled;
    }
}
