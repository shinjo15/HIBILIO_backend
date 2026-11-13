<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\Routine\Application\Usecase\Query\IsRoutineVisible\IsRoutineVisibleInterface;
use Src\Routine\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionDraftFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class CreateRoutineExecutionDraft implements CreateRoutineExecutionDraftInterface
{
    public function __construct(
        private RoutineExecutionDraftFactoryInterface $routineExecutionDraftFactory,
        private RoutineExecutionDraftRepositoryInterface $routineExecutionDraftRepository,
        private IsRoutineVisibleInterface $isRoutineVisible,
    ) {}

    public function execute(CreateRoutineExecutionDraftInputPort $input): void
    {
        if (! $this->isRoutineVisible->execute(
            $input->routineIdentifier()->value(),
            $input->executorAccountIdentifier()->value(),
        )) {
            throw new RoutineNotFoundException;
        }
        $this->routineExecutionDraftRepository->save($this->routineExecutionDraftFactory->create(
            $input->executorAccountIdentifier(),
            $input->routineIdentifier(),
        ));
    }
}
