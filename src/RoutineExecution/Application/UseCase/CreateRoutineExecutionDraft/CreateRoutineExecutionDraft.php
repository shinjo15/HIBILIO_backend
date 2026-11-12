<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateRoutineExecutionDraft implements CreateRoutineExecutionDraftInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private RoutineExecutionDraftRepositoryInterface $draftRepository,
        private RoutineRepositoryInterface $routineRepository,
        private RoutineActionRepositoryInterface $actionRepository,
    ) {}

    public function execute(CreateRoutineExecutionDraftInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $routine = $this->routineRepository->findByIdentifier($input->routineIdentifier())
                ?? throw new RoutineNotFoundException;
            $actions = $this->actionRepository->findByIdentifiers($routine->routineActionIdentifiers()->values());
            $this->draftRepository->save($input->draftCreatorAccountIdentifier(), $routine, $actions);
        });
    }
}
