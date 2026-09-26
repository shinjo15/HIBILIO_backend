<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use DateTimeImmutable;

final readonly class PersistentLoginExpiresAt
{
    public function __construct(
        private DateTimeImmutable $value,
    ) {}

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }
}
