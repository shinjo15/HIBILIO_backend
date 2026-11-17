<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Src\Authentication\Application\Service\LoginPasscodeMailServiceInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Infrastructure\Mail\LoginPasscodeMail;
use Src\Shared\Domain\ValueObject\EmailAddress;

final class LoginPasscodeMailService implements LoginPasscodeMailServiceInterface
{
    public function send(EmailAddress $emailAddress, LoginPasscode $passcode): void
    {
        Mail::to($emailAddress->value())->send(new LoginPasscodeMail($passcode->value()));
    }
}
