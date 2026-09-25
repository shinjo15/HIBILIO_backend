<?php

declare(strict_types=1);

namespace Src\Shared\Application\Service;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AuthServiceInterface
{
    public function login(AccountIdentifier $accountIdentifier): void;

    public function accountIdentifier(): ?string;

    public function logout(): void;
}
