<?php

declare(strict_types=1);

namespace Src\Support\Application\Usecase\Query\GetMySupports;

interface GetMySupportsOutputPort
{
    /**
     * @return list<array{postIdentifier: string, routineIdentifier: string, routineExecutionIdentifier: ?string, postCategory: string, postLikeCount: int, postSupportCount: int, supportedAt: string}>
     */
    public function items(): array;

    public function total(): int;
}
