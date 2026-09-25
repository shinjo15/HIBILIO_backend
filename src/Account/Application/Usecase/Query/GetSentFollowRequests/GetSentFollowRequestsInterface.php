<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

interface GetSentFollowRequestsInterface
{
    public function execute(GetSentFollowRequestsInputPort $input): GetSentFollowRequestsOutputPort;
}
