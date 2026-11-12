<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraft;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftTest extends TestCase
{
    public function test_passes_creator_routine_and_actions_to_repository_in_transaction(): void
    {
        $routineId = new RoutineIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $creator = new AccountIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $actionId = new RoutineActionIdentifier('cccccccc-cccc-4ccc-8ccc-cccccccccccc');
        $routine = Routine::create($routineId, null, new RoutineName('routine'), new RoutineActionIdentifiers([$actionId]), null, $creator, null, null);
        $action = RoutineAction::create($actionId, null, $routineId, new RoutineActionName('action'), null, null, new RoutineActionPosition(0));
        $routines = $this->createMock(RoutineRepositoryInterface::class);
        $routines->expects(self::once())->method('findByIdentifier')->with($routineId)->willReturn($routine);
        $actions = $this->createMock(RoutineActionRepositoryInterface::class);
        $actions->expects(self::once())->method('findByIdentifiers')->with([$actionId])->willReturn([$action]);
        $drafts = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->expects(self::once())->method('save')->with(self::identicalTo($creator), self::identicalTo($routine), self::identicalTo([$action]));
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $transactions->expects(self::once())->method('transaction')->willReturnCallback(static fn (callable $callback) => $callback());

        (new CreateRoutineExecutionDraft($transactions, $drafts, $routines, $actions))
            ->execute(new CreateRoutineExecutionDraftInput($routineId, $creator));
    }

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
