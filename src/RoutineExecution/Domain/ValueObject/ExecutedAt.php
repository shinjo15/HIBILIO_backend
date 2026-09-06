<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\ValueObject;

use DateTimeImmutable;

final readonly class ExecutedAt
{
    public function __construct(
        private DateTimeImmutable $value,
    ) {}

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }
}
