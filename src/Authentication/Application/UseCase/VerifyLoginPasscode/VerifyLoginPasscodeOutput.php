<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyLoginPasscode;

use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class VerifyLoginPasscodeOutput
{
    private function __construct(
        private ?AccountIdentifier $accountIdentifier,
        private ?GeneratePersistentLoginTokenOutputPort $persistentLoginToken,
    ) {}

    public static function authenticated(AccountIdentifier $accountIdentifier, GeneratePersistentLoginTokenOutputPort $persistentLoginToken): self
    {
        return new self($accountIdentifier, $persistentLoginToken);
    }

    public static function rejected(): self
    {
        return new self(null, null);
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function persistentLoginToken(): ?GeneratePersistentLoginTokenOutputPort
    {
        return $this->persistentLoginToken;
    }
}
