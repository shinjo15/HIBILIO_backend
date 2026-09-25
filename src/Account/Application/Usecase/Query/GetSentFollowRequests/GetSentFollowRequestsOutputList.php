<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

final readonly class GetSentFollowRequestsOutputList implements GetSentFollowRequestsOutputPort
{
    /** @param list<GetSentFollowRequestsOutput> $followRequests */
    public function __construct(private array $followRequests) {}

    public function followRequests(): array
    {
        return $this->followRequests;
    }
}
