<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ApproveFollowRequest;

interface ApproveFollowRequestInterface
{
    public function execute(ApproveFollowRequestInputPort $input): void;
}
