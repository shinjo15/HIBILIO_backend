<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Src\Account\Application\Service\AccountRegistrationMailServiceInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Infrastructure\Mail\AccountRegistrationMail;
use Src\Shared\Domain\ValueObject\EmailAddress;

final class LaravelAccountRegistrationMailService implements AccountRegistrationMailServiceInterface
{
    public function send(EmailAddress $emailAddress, AccountName $accountName): void
    {
        Mail::to($emailAddress->value())->send(new AccountRegistrationMail($accountName->value()));
    }
}
