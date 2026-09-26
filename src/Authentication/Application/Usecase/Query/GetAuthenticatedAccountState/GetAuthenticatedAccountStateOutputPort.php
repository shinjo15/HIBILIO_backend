<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

interface GetAuthenticatedAccountStateOutputPort
{
    public function isAuthenticated(): bool;
}
