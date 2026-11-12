<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

final readonly class CreateRoutineExecutionDraftOutput implements CreateRoutineExecutionDraftOutputPort
{
    public function __construct(private bool $created) {}

    public function created(): bool
    {
        return $this->created;
    }
}
