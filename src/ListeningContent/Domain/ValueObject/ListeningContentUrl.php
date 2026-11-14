<?php

declare(strict_types=1);

namespace Src\ListeningContent\Domain\ValueObject;

use Src\ListeningContent\Domain\Exception\InvalidListeningContentUrlException;
use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class ListeningContentUrl extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidListeningContentUrlException;
        }
    }
}
