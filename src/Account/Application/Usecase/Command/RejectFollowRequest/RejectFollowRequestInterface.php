<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RejectFollowRequest;

interface RejectFollowRequestInterface
{
    public function execute(RejectFollowRequestInputPort $input): void;
}
