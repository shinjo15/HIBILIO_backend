<?php

declare(strict_types=1);

namespace Src\Contact\Application\Service;

use Src\Contact\Domain\Entity\Contact;
use Src\Shared\Domain\ValueObject\EmailAddress;

interface ContactSupportMailServiceInterface
{
    public function send(Contact $contact, EmailAddress $replyTo): void;
}
