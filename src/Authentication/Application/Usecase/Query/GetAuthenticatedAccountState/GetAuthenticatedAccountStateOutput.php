<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

final readonly class GetAuthenticatedAccountStateOutput implements GetAuthenticatedAccountStateOutputPort
{
    public function __construct(private bool $authenticated) {}

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }
}
