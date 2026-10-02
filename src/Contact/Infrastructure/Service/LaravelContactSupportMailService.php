<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Infrastructure\Mail\ContactSupportMail;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LaravelContactSupportMailService implements ContactSupportMailServiceInterface
{
    public function send(AccountIdentifier $accountIdentifier, EmailAddress $replyTo, string $title, string $content): void
    {
        Mail::to((string) config('contact.support_mail_address'))->send(new ContactSupportMail($accountIdentifier->value(), $replyTo->value(), $title, $content));
    }
}
