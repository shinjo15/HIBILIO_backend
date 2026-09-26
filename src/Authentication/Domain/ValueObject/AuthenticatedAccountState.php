<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use Src\Account\Domain\ValueObject\AccountStatus;

final readonly class AuthenticatedAccountState
{
    public function __construct(
        private bool $available,
        private AccountStatus $status,
    ) {}

    public function available(): bool
    {
        return $this->available;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }
}
