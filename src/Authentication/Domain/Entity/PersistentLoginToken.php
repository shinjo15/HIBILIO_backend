<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entity;

use DateTimeImmutable;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class PersistentLoginToken
{
    public function __construct(
        private PersistentLoginSelector $selector,
        private AccountIdentifier $accountIdentifier,
        private PersistentLoginValidatorHash $validatorHash,
        private PersistentLoginExpiresAt $expiresAt,
    ) {}

    public function selector(): PersistentLoginSelector
    {
        return $this->selector;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function validatorHash(): PersistentLoginValidatorHash
    {
        return $this->validatorHash;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt->value() <= $now;
    }

    public function expiresAt(): PersistentLoginExpiresAt
    {
        return $this->expiresAt;
    }
}
