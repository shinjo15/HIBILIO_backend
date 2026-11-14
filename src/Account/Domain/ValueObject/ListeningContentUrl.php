<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class ListeningContentUrl extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('コンテンツのURLは有効な形式である必要があります。');
        }
    }
}
