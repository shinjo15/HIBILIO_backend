<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

interface GetSentFollowRequestsOutputPort
{
    /** @return list<GetSentFollowRequestsOutput> */
    public function followRequests(): array;
}
