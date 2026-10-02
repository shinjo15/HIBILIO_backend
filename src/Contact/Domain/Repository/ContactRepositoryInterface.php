<?php

declare(strict_types=1);

namespace Src\Contact\Domain\Repository;

use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\ValueObject\ContactIdentifier;

interface ContactRepositoryInterface
{
    public function find(ContactIdentifier $contactIdentifier): ?Contact;

    public function save(Contact $contact): void;
}
