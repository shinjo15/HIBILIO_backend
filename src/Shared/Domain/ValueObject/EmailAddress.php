<?php

declare(strict_types=1);

namespace Src\Shared\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class EmailAddress extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('メールアドレスは有効な形式である必要があります。');
        }
    }
}
