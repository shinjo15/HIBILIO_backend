<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Shared\Domain\ValueObject\EmailAddress;

interface LoginPasscodeMailServiceInterface
{
    public function send(EmailAddress $emailAddress, LoginPasscode $passcode): void;
}
