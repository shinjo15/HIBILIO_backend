<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

use Src\Account\Domain\ValueObject\EmailAddress;

final readonly class VerifyRegistrationPasscodeOutput
{
    private function __construct(private ?EmailAddress $emailAddress) {}

    public static function rejected(): self
    {
        return new self(null);
    }

    public static function verified(EmailAddress $emailAddress): self
    {
        return new self($emailAddress);
    }

    public function emailAddress(): ?EmailAddress
    {
        return $this->emailAddress;
    }
}
