<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetReceivedFollowRequests;

interface GetReceivedFollowRequestsInterface
{
    public function execute(GetReceivedFollowRequestsInputPort $input): GetReceivedFollowRequestsOutputPort;
}
