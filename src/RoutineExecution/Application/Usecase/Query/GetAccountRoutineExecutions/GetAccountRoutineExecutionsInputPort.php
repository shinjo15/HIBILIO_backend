<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions;

interface GetAccountRoutineExecutionsInputPort
{
    public function accountIdentifier(): string;

    public function viewerAccountIdentifier(): ?string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;

    public function start(): ?string;

    public function end(): ?string;
}
