<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CompleteSocialLoginOutput implements CompleteSocialLoginOutputPort
{
    private function __construct(private bool $authenticated, private ?AccountIdentifier $accountIdentifier) {}

    public static function authenticated(AccountIdentifier $accountIdentifier): self
    {
        return new self(true, $accountIdentifier);
    }

    public static function rejected(): self
    {
        return new self(false, null);
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
