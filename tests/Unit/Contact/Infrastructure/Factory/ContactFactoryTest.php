<?php

declare(strict_types=1);

namespace Tests\Unit\Contact\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Contact\Infrastructure\Factory\ContactFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ContactFactoryTest extends TestCase
{
    public function test_creates_an_unresolved_contact_with_the_uuid_service_identifier(): void
    {
        $contact = (new ContactFactory(new FixedContactUuidService))->create(
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new ContactTitle('お問い合わせ'),
            new ContactContent('内容です。'),
        );

        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $contact->contactIdentifier()->value());
        self::assertNull($contact->sendStatus());
    }
}

final class FixedContactUuidService implements UuidServiceInterface
{
    public function generate(): string
    {
        return '3b5581e9-16df-4879-b7d2-5d88dca6ab87';
    }
}
