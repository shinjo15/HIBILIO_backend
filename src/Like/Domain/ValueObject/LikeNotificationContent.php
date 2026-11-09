<?php

declare(strict_types=1);

namespace Src\Like\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class LikeNotificationContent extends StringValueObject
{
    protected function validate(string $value): void {}
}
