<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Application\Service\RegistrationPasscodeMailServiceInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Infrastructure\Mail\RegistrationPasscodeMail;

final class RegistrationPasscodeMailService implements RegistrationPasscodeMailServiceInterface
{
    public function send(EmailAddress $emailAddress, LoginPasscode $passcode): void
    {
        Mail::to($emailAddress->value())->send(new RegistrationPasscodeMail($passcode->value()));
    }
}
