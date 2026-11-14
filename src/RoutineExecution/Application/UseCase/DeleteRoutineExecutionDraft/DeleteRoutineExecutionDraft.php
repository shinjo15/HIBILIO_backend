<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft;

use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class DeleteRoutineExecutionDraft implements DeleteRoutineExecutionDraftInterface
{
    public function __construct(private RoutineExecutionDraftRepositoryInterface $draftRepository) {}

    public function execute(DeleteRoutineExecutionDraftInputPort $input): void
    {
        $draft = $this->draftRepository->find($input->executorAccountIdentifier(), $input->routineIdentifier()) ?? throw new RoutineExecutionDraftNotFoundException;
        $this->draftRepository->delete($draft);
    }
}
