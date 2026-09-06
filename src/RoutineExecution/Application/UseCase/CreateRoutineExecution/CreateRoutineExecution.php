<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateRoutineExecution implements CreateRoutineExecutionInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private RoutineExecutionFactoryInterface $routineExecutionFactory,
        private RoutineExecutionActionFactoryInterface $routineExecutionActionFactory,
        private RoutineExecutionRepositoryInterface $routineExecutionRepository,
        private RoutineExecutionActionRepositoryInterface $routineExecutionActionRepository,
        private RoutineActionRepositoryInterface $routineActionRepository,

    ) {}

    public function execute(CreateRoutineExecutionInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $this->validateRoutineActions($input);
            $routineExecution = $this->routineExecutionFactory->create(
                $input->executorAccountIdentifier(),
                $input->routineIdentifier(),
                $input->routineExecutionMemo(),
            );
            $this->routineExecutionRepository->save($routineExecution);

            foreach ($input->executedRoutineActionIdentifiers() as $routineActionIdentifier) {
                $this->routineExecutionActionRepository->save(
                    $this->routineExecutionActionFactory->create(
                        $routineExecution->routineExecutionIdentifier(),
                        $routineActionIdentifier,
                    ),
                );
            }

        });
    }

    private function validateRoutineActions(CreateRoutineExecutionInputPort $input): void
    {
        $routineActions = $this->routineActionRepository->findByIdentifiers(
            $input->executedRoutineActionIdentifiers(),
        );

        if (count($routineActions) !== count($input->executedRoutineActionIdentifiers())) {
            throw new \DomainException('指定したルーティン行動が見つかりません。');
        }

        foreach ($routineActions as $routineAction) {
            if ($routineAction->routineIdentifier()->value() !== $input->routineIdentifier()->value()) {
                throw new \DomainException('指定したルーティンに属さない行動は実行できません。');
            }
        }
    }
}
