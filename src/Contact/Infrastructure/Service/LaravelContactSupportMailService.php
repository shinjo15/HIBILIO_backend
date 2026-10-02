<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Service;

use Illuminate\Support\Facades\Mail;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Infrastructure\Mail\ContactSupportMail;

final class LaravelContactSupportMailService implements ContactSupportMailServiceInterface
{
    public function send(Contact $contact, EmailAddress $replyTo): void
    {
        Mail::to((string) config('contact.support_mail_address'))->send(new ContactSupportMail($contact->accountIdentifier()->value(), $replyTo->value(), $contact->title()->value(), $contact->content()->value()));
    }
}
