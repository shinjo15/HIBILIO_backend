<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entity;

use DateTimeImmutable;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class PersistentLoginToken
{
    public function __construct(
        private string $selector,
        private AccountIdentifier $accountIdentifier,
        private string $validatorHash,
        private DateTimeImmutable $expiresAt,
    ) {}

    public function selector(): string
    {
        return $this->selector;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function validatorHash(): string
    {
        return $this->validatorHash;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
