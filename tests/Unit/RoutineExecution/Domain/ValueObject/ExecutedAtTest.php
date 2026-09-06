<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;

final class ExecutedAtTest extends TestCase
{
    public function test_retains_the_execution_datetime(): void
    {
        $executedAt = new DateTimeImmutable('2026-09-06 12:34:56');

        $valueObject = new ExecutedAt($executedAt);

        self::assertSame($executedAt, $valueObject->value());
    }
}
