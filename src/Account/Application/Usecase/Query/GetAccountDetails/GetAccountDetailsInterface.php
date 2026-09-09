<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

interface GetAccountDetailsInterface
{
    public function execute(GetAccountDetailsInputPort $input): GetAccountDetailsOutputPort;
}
