<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\RoutineExecution\Domain\Factory\RoutineExecutionDraftFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class CreateRoutineExecutionDraft implements CreateRoutineExecutionDraftInterface
{
    public function __construct(
        private RoutineExecutionDraftFactoryInterface $routineExecutionDraftFactory,
        private RoutineExecutionDraftRepositoryInterface $routineExecutionDraftRepository,
    ) {}

    public function execute(CreateRoutineExecutionDraftInputPort $input): void
    {
        $this->routineExecutionDraftRepository->save($this->routineExecutionDraftFactory->create(
            $input->executorAccountIdentifier(),
            $input->routineIdentifier(),
        ));
    }
}
