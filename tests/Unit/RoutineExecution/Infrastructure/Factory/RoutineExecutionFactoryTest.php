<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Infrastructure\Factory\RoutineExecutionFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionFactoryTest extends TestCase
{
    public function test_creates_an_execution_with_a_generated_identifier(): void
    {
        $factory = new RoutineExecutionFactory(new class implements UuidServiceInterface
        {
            public function generate(): string
            {
                return 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
            }
        });

        $execution = $factory->create(
            new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'),
            new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
            null,
        );

        self::assertSame('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $execution->routineExecutionIdentifier()->value());
        self::assertNull($execution->routineExecutionMemo());
    }
}
