<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

interface GetAuthenticatedAccountStateInterface
{
    public function execute(GetAuthenticatedAccountStateInputPort $input): GetAuthenticatedAccountStateOutputPort;
}
