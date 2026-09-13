<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface CompleteSocialLoginOutputPort
{
    public function isAuthenticated(): bool;

    public function accountIdentifier(): ?AccountIdentifier;
}
