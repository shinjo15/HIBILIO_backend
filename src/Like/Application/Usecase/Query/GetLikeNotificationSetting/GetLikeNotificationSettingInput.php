<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikeNotificationSetting;

final readonly class GetLikeNotificationSettingInput implements GetLikeNotificationSettingInputPort
{
    public function __construct(private string $accountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
