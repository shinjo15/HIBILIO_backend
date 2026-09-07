<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

use Src\Account\Domain\ValueObject\EmailAddress;

interface VerifyRegistrationPasscodeOutputPort
{
    public function emailAddress(): ?EmailAddress;
}
