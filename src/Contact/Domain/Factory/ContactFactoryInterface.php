<?php

declare(strict_types=1);

namespace Src\Contact\Domain\Factory;

use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ContactFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, ContactTitle $title, ContactContent $content): Contact;
}
