<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetReceivedFollowRequests;

interface GetReceivedFollowRequestsInputPort
{
    public function targetAccountIdentifier(): string;
}
