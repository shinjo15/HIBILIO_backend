<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class AuthenticateWithPersistentLoginTokenOutput implements AuthenticateWithPersistentLoginTokenOutputPort
{
    public function __construct(private ?AccountIdentifier $accountIdentifier, private ?GeneratePersistentLoginTokenOutputPort $persistentLoginToken) {}

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function persistentLoginToken(): ?GeneratePersistentLoginTokenOutputPort
    {
        return $this->persistentLoginToken;
    }
}
