<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetLikeNotificationSetting;

final readonly class GetLikeNotificationSettingOutput implements GetLikeNotificationSettingOutputPort
{
    public function __construct(private ?bool $enabled) {}

    public function enabled(): ?bool
    {
        return $this->enabled;
    }
}
