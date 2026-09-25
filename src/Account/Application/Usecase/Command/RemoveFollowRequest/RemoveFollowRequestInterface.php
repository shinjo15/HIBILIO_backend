<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveFollowRequest;

interface RemoveFollowRequestInterface
{
    public function execute(RemoveFollowRequestInputPort $input): void;
}
