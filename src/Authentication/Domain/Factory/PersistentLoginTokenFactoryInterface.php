<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Factory;

use DateTimeImmutable;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface PersistentLoginTokenFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, DateTimeImmutable $issuedAt): GeneratedPersistentLoginToken;
}
