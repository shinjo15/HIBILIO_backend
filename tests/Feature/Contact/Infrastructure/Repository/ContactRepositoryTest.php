<?php

declare(strict_types=1);

namespace Tests\Feature\Contact\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Domain\ValueObject\ContactSendStatus;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Contact\Infrastructure\Repository\ContactRepository;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class ContactRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_updates_and_restores_contact_delivery_state(): void
    {
        $this->insertAccount();
        $contact = Contact::create(
            new ContactIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new ContactTitle('お問い合わせ'),
            new ContactContent('内容です。'),
        );
        $repository = new ContactRepository;

        $repository->save($contact);
        self::assertDatabaseHas('contacts', [
            'contact_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            'title' => 'お問い合わせ',
            'content' => '内容です。',
            'status' => 'pending',
            'sent_at' => null,
        ]);

        $contact->markSent(new DateTimeImmutable('2026-10-02 12:00:00'));
        $repository->save($contact);
        $restored = $repository->find(new ContactIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'));

        self::assertSame(ContactSendStatus::Sent, $restored?->sendStatus());
        self::assertSame('2026-10-02 12:00:00', $restored?->sentAt()?->format('Y-m-d H:i:s'));
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $restored?->accountIdentifier()->value());
    }

    private function insertAccount(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            'account_name' => 'お問い合わせユーザー',
            'email_address' => 'account@example.com',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
