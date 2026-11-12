<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraft;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftTest extends TestCase
{
    public function test_missing_routine_does_not_save_draft(): void
    {
        $routineId = new RoutineIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $draftCreatorAccountIdentifier = new AccountIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $routines = $this->createMock(RoutineRepositoryInterface::class);
        $routines->expects(self::once())->method('findByIdentifier')->with($routineId)->willReturn(null);
        $drafts = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->expects(self::never())->method('save');
        $actions = $this->createMock(RoutineActionRepositoryInterface::class);
        $actions->expects(self::never())->method('findByIdentifiers');
        $transactions = new class implements TransactionManagerInterface
        {
            public function transaction(callable $callback): mixed
            {
                return $callback();
            }
        };
        $useCase = new CreateRoutineExecutionDraft($transactions, $drafts, $routines, $actions);

        $this->expectException(RoutineNotFoundException::class);
        $useCase->execute(new CreateRoutineExecutionDraftInput($routineId, $draftCreatorAccountIdentifier));
    }
}
