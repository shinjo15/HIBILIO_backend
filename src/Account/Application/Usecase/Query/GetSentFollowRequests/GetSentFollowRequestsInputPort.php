<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

interface GetSentFollowRequestsInputPort
{
    public function requestingAccountIdentifier(): string;
}
