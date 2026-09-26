<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\RestorePersistentLogin;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RestorePersistentLoginOutput implements RestorePersistentLoginOutputPort
{
    public function __construct(private ?AccountIdentifier $accountIdentifier) {}

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
