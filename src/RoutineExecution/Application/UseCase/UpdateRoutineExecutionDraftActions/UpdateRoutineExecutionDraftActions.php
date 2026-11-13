<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions;

use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class UpdateRoutineExecutionDraftActions implements UpdateRoutineExecutionDraftActionsInterface
{
    public function __construct(
        private RoutineExecutionDraftRepositoryInterface $repository,
        private RoutineExecutionActionDraftRepositoryInterface $actionDraftRepository,
        private RoutineActionRepositoryInterface $routineActionRepository,
        private RoutineExecutionActionFactoryInterface $factory,
    ) {}

    public function execute(UpdateRoutineExecutionDraftActionsInputPort $input): void
    {
        $draft = $this->repository->find($input->executorAccountIdentifier(), $input->routineIdentifier())
            ?? throw new RoutineExecutionDraftNotFoundException;
        $identifier = $input->routineActionIdentifier();
        $routineActions = $this->routineActionRepository->findByIdentifiers([$identifier]);
        if (count($routineActions) !== 1) {
            throw new \DomainException('指定したルーティン行動が見つかりません。');
        }
        foreach ($routineActions as $action) {
            if ($action->routineIdentifier()->value() !== $draft->routineIdentifier()->value()) {
                throw new \DomainException('指定したルーティンに属さない行動は実行できません。');
            }
        }

        $action = $this->factory->create($draft->routineExecutionIdentifier(), $identifier);
        if ($input->isExecuted()) {
            $this->actionDraftRepository->save($action, $draft);
        } else {
            $this->actionDraftRepository->delete($action, $draft);
        }
    }
}
