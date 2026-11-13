<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class CreateRoutineExecutionDraft implements CreateRoutineExecutionDraftInterface
{
    public function __construct(
        private RoutineExecutionFactoryInterface $routineExecutionFactory,
        private RoutineExecutionDraftRepositoryInterface $routineExecutionDraftRepository,
    ) {}

    public function execute(CreateRoutineExecutionDraftInputPort $input): void
    {
        $this->routineExecutionDraftRepository->save($this->routineExecutionFactory->create(
            $input->executorAccountIdentifier(),
            $input->routineIdentifier(),
            null,
        ));
    }
}
