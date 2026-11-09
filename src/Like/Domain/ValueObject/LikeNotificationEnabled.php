<?php

declare(strict_types=1);

namespace Src\Like\Domain\ValueObject;

final readonly class LikeNotificationEnabled
{
    public function __construct(private bool $value) {}

    public function value(): bool
    {
        return $this->value;
    }
}
