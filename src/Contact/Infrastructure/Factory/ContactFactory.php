<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Factory;

use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\Factory\ContactFactoryInterface;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ContactFactory implements ContactFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountIdentifier $accountIdentifier, ContactTitle $title, ContactContent $content): Contact
    {
        return Contact::create(new ContactIdentifier($this->uuidService->generate()), $accountIdentifier, $title, $content);
    }
}
