<?php

declare(strict_types=1);

namespace Src\Contact\Application\Service;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Contact\Domain\Entity\Contact;

interface ContactSupportMailServiceInterface
{
    public function send(Contact $contact, EmailAddress $replyTo): void;
}
