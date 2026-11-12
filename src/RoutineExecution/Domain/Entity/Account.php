<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class Account
{
    public function __construct(private readonly AccountIdentifier $accountIdentifier, private int $experience, private readonly AccountStatus $status, private readonly bool $available)
    {
        if ($experience < 0) {
            throw new \InvalidArgumentException('経験値は0以上である必要があります。');
        }
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function experience(): int
    {
        return $this->experience;
    }

    public function canAuthenticate(): bool
    {
        return $this->available && $this->status === AccountStatus::ACTIVE;
    }

    public function recordRoutineExecution(): void
    {
        $this->experience += 100;
    }
}
