<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CompleteSocialLoginOutput implements CompleteSocialLoginOutputPort
{
    private function __construct(
        private bool $authenticated,
        private ?AccountIdentifier $accountIdentifier,
        private ?PendingSocialRegistration $pendingRegistration,
    ) {}

    public static function authenticated(AccountIdentifier $accountIdentifier): self
    {
        return new self(true, $accountIdentifier, null);
    }

    public static function pending(PendingSocialRegistration $registration): self
    {
        return new self(false, null, $registration);
    }

    public static function rejected(): self
    {
        return new self(false, null, null);
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function pendingRegistration(): ?PendingSocialRegistration
    {
        return $this->pendingRegistration;
    }
}
