<?php

declare(strict_types=1);

namespace Src\Support\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class SupportNotificationContent extends StringValueObject
{
    protected function validate(string $value): void {}
}
