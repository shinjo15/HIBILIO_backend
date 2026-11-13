<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final readonly class UpdateRoutineExecutionDraftMemo implements UpdateRoutineExecutionDraftMemoInterface
{
    public function __construct(private RoutineExecutionDraftRepositoryInterface $repository) {}

    public function execute(UpdateRoutineExecutionDraftMemoInputPort $input): void
    {
        $draft = $this->repository->find($input->executorAccountIdentifier(), $input->routineIdentifier());
        if ($draft === null) {
            throw new RoutineExecutionDraftNotFoundException;
        }

        $draft->changeMemo($input->memo());
        $this->repository->updateMemo($draft);
    }
}
