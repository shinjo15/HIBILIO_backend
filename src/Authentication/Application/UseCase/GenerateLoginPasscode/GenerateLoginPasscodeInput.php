<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateLoginPasscode;

use Src\Shared\Domain\ValueObject\EmailAddress;

final readonly class GenerateLoginPasscodeInput
{
    public function __construct(private EmailAddress $emailAddress) {}

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
