<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions\UpdateRoutineExecutionDraftActions;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions\UpdateRoutineExecutionDraftActionsInput;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class UpdateRoutineExecutionDraftActionsTest extends TestCase
{
    public function test_check_and_uncheck_delegate_one_action(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $routine = new RoutineIdentifier('22222222-2222-4222-8222-222222222222');
        $execution = new RoutineExecutionIdentifier('33333333-3333-4333-8333-333333333333');
        $id = new RoutineActionIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $draft = new RoutineExecutionDraft($execution, $account, $routine, new ExecutedAt(new DateTimeImmutable), null);
        $action = new RoutineExecutionAction($execution, $id);
        $routineAction = RoutineAction::create($id, null, $routine, new RoutineActionName('行動'), null, null, new RoutineActionPosition(0));
        $drafts = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->expects($this->exactly(2))->method('find')->with($account, $routine)->willReturn($draft);
        $routineActions = $this->createStub(RoutineActionRepositoryInterface::class);
        $routineActions->method('findByIdentifiers')->willReturn([$routineAction]);
        $factory = $this->createMock(RoutineExecutionActionFactoryInterface::class);
        $factory->expects($this->exactly(2))->method('create')->with($execution, $id)->willReturn($action);
        $actions = $this->createMock(RoutineExecutionActionDraftRepositoryInterface::class);
        $actions->expects($this->once())->method('save')->with($action, $draft);
        $actions->expects($this->once())->method('delete')->with($action, $draft);
        $useCase = new UpdateRoutineExecutionDraftActions($drafts, $actions, $routineActions, $factory);
        $useCase->execute(new UpdateRoutineExecutionDraftActionsInput($account, $routine, $id, true));
        $useCase->execute(new UpdateRoutineExecutionDraftActionsInput($account, $routine, $id, false));
    }

    public function test_missing_own_draft_does_not_write(): void
    {
        $drafts = $this->createStub(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->method('find')->willReturn(null);
        $actions = $this->createMock(RoutineExecutionActionDraftRepositoryInterface::class);
        $actions->expects($this->never())->method('save');
        $actions->expects($this->never())->method('delete');
        $useCase = new UpdateRoutineExecutionDraftActions($drafts, $actions, $this->createStub(RoutineActionRepositoryInterface::class), $this->createStub(RoutineExecutionActionFactoryInterface::class));
        $this->expectException(RoutineExecutionDraftNotFoundException::class);
        $useCase->execute(new UpdateRoutineExecutionDraftActionsInput(
            new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
            new RoutineIdentifier('22222222-2222-4222-8222-222222222222'),
            new RoutineActionIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'),
            true,
        ));
    }
}
