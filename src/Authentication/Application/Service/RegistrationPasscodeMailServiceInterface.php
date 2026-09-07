<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\ValueObject\LoginPasscode;

interface RegistrationPasscodeMailServiceInterface
{
    public function send(EmailAddress $emailAddress, LoginPasscode $passcode): void;
}
