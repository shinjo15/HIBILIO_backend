<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

use Src\Account\Domain\ValueObject\EmailAddress;

final readonly class GenerateRegistrationPasscodeInput implements GenerateRegistrationPasscodeInputPort
{
    public function __construct(private EmailAddress $emailAddress) {}

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
