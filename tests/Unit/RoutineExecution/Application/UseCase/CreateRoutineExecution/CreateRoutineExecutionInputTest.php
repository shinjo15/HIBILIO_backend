<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionInputTest extends TestCase
{
    public function test_rejects_an_empty_list_of_executed_routine_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('実行した行動は1件以上指定する必要があります。');

        new CreateRoutineExecutionInput(
            new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'),
            new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
            [],
            null,
        );
    }

    public function test_rejects_duplicate_executed_routine_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('実行した行動は重複して指定できません。');

        new CreateRoutineExecutionInput(
            new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'),
            new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
            [
                new RoutineActionIdentifier('10000000-0000-4000-8000-000000000001'),
                new RoutineActionIdentifier('10000000-0000-4000-8000-000000000001'),
            ],
            null,
        );
    }
}
