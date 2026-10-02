<?php

declare(strict_types=1);

namespace Tests\Unit\Contact\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Domain\ValueObject\ContactSendStatus;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ContactTest extends TestCase
{
    public function test_creates_a_pending_contact_and_transitions_to_sent_once(): void
    {
        $contact = Contact::create(
            new ContactIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new ContactTitle('お問い合わせ'),
            new ContactContent('内容です。'),
        );
        $sentAt = new DateTimeImmutable('2026-10-02 12:00:00');

        self::assertSame(ContactSendStatus::Pending, $contact->sendStatus());
        self::assertNull($contact->sentAt());
        $contact->markSent($sentAt);

        self::assertSame(ContactSendStatus::Sent, $contact->sendStatus());
        self::assertSame($sentAt, $contact->sentAt());
        $this->expectException(\LogicException::class);
        $contact->markSent($sentAt);
    }

    public function test_transitions_pending_contact_to_failed_without_a_sent_at(): void
    {
        $contact = Contact::create(
            new ContactIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new ContactTitle('お問い合わせ'),
            new ContactContent('内容です。'),
        );

        $contact->markFailed();

        self::assertSame(ContactSendStatus::Failed, $contact->sendStatus());
        self::assertNull($contact->sentAt());
        $this->expectException(\LogicException::class);
        $contact->markFailed();
    }

    public function test_rejects_invalid_contact_value_objects(): void
    {
        $invalidValueObjectCount = 0;

        foreach ([['title' => '', 'content' => '内容です。'], ['title' => '   ', 'content' => '内容です。'], ['title' => str_repeat('あ', 51), 'content' => '内容です。'], ['title' => "お問い合わせ\r\nBcc: attacker@example.com", 'content' => '内容です。'], ['title' => "お問い合わせ\u{2028}Bcc: attacker@example.com", 'content' => '内容です。'], ['title' => 'お問い合わせ', 'content' => ''], ['title' => 'お問い合わせ', 'content' => '   '], ['title' => 'お問い合わせ', 'content' => str_repeat('あ', 501)]] as $values) {
            try {
                new ContactTitle($values['title']);
                new ContactContent($values['content']);
            } catch (\InvalidArgumentException) {
                $invalidValueObjectCount++;

                continue;
            }

            self::fail('Expected an invalid contact value object.');
        }

        self::assertSame(8, $invalidValueObjectCount);
    }
}
