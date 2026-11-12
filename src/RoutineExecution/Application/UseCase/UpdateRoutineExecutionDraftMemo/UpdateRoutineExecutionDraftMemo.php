<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class UpdateRoutineExecutionDraftMemo implements UpdateRoutineExecutionDraftMemoInterface
{
    public function __construct(private RoutineExecutionDraftRepositoryInterface $drafts) {}

    public function execute(UpdateRoutineExecutionDraftMemoInputPort $input): void
    {
        if (! $this->drafts->updateMemo($input->draftCreatorAccountIdentifier(), $input->routineIdentifier(), $input->routineExecutionMemo())) {
            throw new RoutineExecutionDraftNotFoundException;
        }
    }
}
