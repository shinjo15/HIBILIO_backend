<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines;

interface GetRecentExecutedRoutinesInterface
{
    /** @return array{items: list<array{post_identifier: ?string, liked: ?bool, ...}>, total: int} */
    public function execute(string $accountIdentifier, int $page, int $numberOfItemsPerPage): array;
}
