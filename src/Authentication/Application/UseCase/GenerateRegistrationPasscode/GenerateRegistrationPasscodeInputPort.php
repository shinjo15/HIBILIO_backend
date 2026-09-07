<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

use Src\Account\Domain\ValueObject\EmailAddress;

interface GenerateRegistrationPasscodeInputPort
{
    public function emailAddress(): EmailAddress;
}
