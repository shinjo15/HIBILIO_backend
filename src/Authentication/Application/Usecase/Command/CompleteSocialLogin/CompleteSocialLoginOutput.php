<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutputPort;
use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CompleteSocialLoginOutput implements CompleteSocialLoginOutputPort
{
    private function __construct(
        private bool $authenticated,
        private ?AccountIdentifier $accountIdentifier,
        private ?PendingSocialRegistration $pendingRegistration,
        private ?GeneratePersistentLoginTokenOutputPort $persistentLoginToken,
    ) {}

    public static function authenticated(AccountIdentifier $accountIdentifier, GeneratePersistentLoginTokenOutputPort $persistentLoginToken): self
    {
        return new self(true, $accountIdentifier, null, $persistentLoginToken);
    }

    public static function pending(PendingSocialRegistration $registration): self
    {
        return new self(false, null, $registration, null);
    }

    public static function rejected(): self
    {
        return new self(false, null, null, null);
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

    public function persistentLoginToken(): ?GeneratePersistentLoginTokenOutputPort
    {
        return $this->persistentLoginToken;
    }
}
