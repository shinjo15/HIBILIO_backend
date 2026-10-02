<?php

declare(strict_types=1);

namespace Tests\Feature\Contact\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Domain\Entity\Contact;
use Src\Contact\Domain\Repository\AccountRepositoryInterface as ContactAccountRepositoryInterface;
use Src\Contact\Domain\Repository\ContactRepositoryInterface;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Infrastructure\Repository\ContactRepository;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class SendContactSupportMailActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_contact_mail_to_the_configured_support_address_with_the_authenticated_account_as_reply_to(): void
    {
        Mail::fake();
        config([
            'contact.support_mail_address' => 'hibilio.support@gmail.com',
            'mail.from.address' => 'no-reply@hibilio.example',
            'mail.from.name' => 'HIBILIO',
        ]);
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');

        $response = $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です']);

        $response->assertNoContent();
        $this->assertDatabaseHas('contacts', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'title' => 'お問い合わせ',
            'content' => '内容です',
            'status' => 'sent',
        ]);
        self::assertNotNull(DB::table('contacts')->value('sent_at'));
        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', function ($mail): bool {
            self::assertTrue($mail->hasTo('hibilio.support@gmail.com'));
            self::assertTrue($mail->build()->hasReplyTo('account@example.com'));
            self::assertTrue($mail->hasFrom('no-reply@hibilio.example'));
            self::assertStringContainsString('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $mail->render());
            self::assertStringContainsString('お問い合わせ', $mail->render());
            self::assertStringContainsString('内容です', $mail->render());

            return true;
        });
    }

    public function test_uses_the_contact_account_repository_email_address_as_reply_to(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $this->app->instance(ContactAccountRepositoryInterface::class, new class implements ContactAccountRepositoryInterface
        {
            public function findEmailAddress(AccountIdentifier $accountIdentifier): ?EmailAddress
            {
                return new EmailAddress('contact-repository@example.com');
            }
        });

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertNoContent();

        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', function ($mail): bool {
            self::assertTrue($mail->build()->hasReplyTo('contact-repository@example.com'));

            return true;
        });
    }

    public function test_does_not_send_or_persist_when_the_contact_account_repository_returns_no_email_address(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $this->app->instance(ContactAccountRepositoryInterface::class, new class implements ContactAccountRepositoryInterface
        {
            public function findEmailAddress(AccountIdentifier $accountIdentifier): ?EmailAddress
            {
                return null;
            }
        });

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertUnauthorized();

        Mail::assertNothingSent();
        self::assertSame(0, DB::table('contacts')->count());
    }

    public function test_has_no_persisted_contact_while_mail_sends_then_saves_sent_contact_once(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $mailService = new class implements ContactSupportMailServiceInterface
        {
            public int $contactCountWhenSending = -1;

            public function send(Contact $contact, EmailAddress $replyTo): void
            {
                $this->contactCountWhenSending = DB::table('contacts')->count();
            }
        };
        $this->app->instance(ContactSupportMailServiceInterface::class, $mailService);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertNoContent();

        self::assertSame(0, $mailService->contactCountWhenSending);
        $this->assertDatabaseHas('contacts', [
            'status' => 'sent',
        ]);
        self::assertNotNull(DB::table('contacts')->value('sent_at'));
        self::assertSame(1, DB::table('contacts')->count());
    }

    public function test_validates_required_nonblank_unicode_length_and_string_contact_fields(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => str_repeat('あ', 50), 'content' => str_repeat('い', 500)])
            ->assertNoContent();

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => str_repeat('あ', 51), 'content' => str_repeat('い', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => '  ', 'content' => '  '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);

        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'other-account@example.com');
        $this->withSession(['account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'])
            ->postJson('/api/contact-support', ['title' => 1, 'content' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_rejects_line_separators_in_title_and_missing_contact_fields_without_sending_mail(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => "お問い合わせ\r\nBcc: attacker@example.com", 'content' => '内容です'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => "お問い合わせ\u{2028}Bcc: attacker@example.com", 'content' => '内容です'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);

        Mail::assertNothingSent();
    }

    public function test_preserves_special_characters_in_the_plaintext_contact_mail_body(): void
    {
        Mail::fake();
        $accountIdentifier = '3b5581e9-16df-4879-b7d2-5d88dca6ab87';
        $title = '元のタイトル';
        $content = 'A & B < C > D "引用" \'single\'';
        $this->insertAccount($accountIdentifier, 'account@example.com');

        $this->withSession(['account_identifier' => $accountIdentifier])
            ->postJson('/api/contact-support', ['title' => $title, 'content' => $content])
            ->assertNoContent();

        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', function ($mail) use ($accountIdentifier, $title, $content): bool {
            $rendered = $mail->render();

            self::assertStringContainsString("アカウントID: {$accountIdentifier}", $rendered);
            self::assertStringContainsString("お問い合わせタイトル:\n{$title}", $rendered);
            self::assertStringContainsString("お問い合わせ内容:\n{$content}", $rendered);

            return true;
        });
    }

    public function test_returns_an_error_without_success_when_contact_mail_sending_fails(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $mailService = new class implements ContactSupportMailServiceInterface
        {
            public int $contactCountWhenSending = -1;

            public function send(Contact $contact, EmailAddress $replyTo): void
            {
                $this->contactCountWhenSending = DB::table('contacts')->count();
                throw new \RuntimeException('Mail transport failed.');
            }
        };
        $this->app->instance(ContactSupportMailServiceInterface::class, $mailService);
        $repository = new class(new ContactRepository) implements ContactRepositoryInterface
        {
            private int $saveCount = 0;

            public function __construct(private ContactRepository $repository) {}

            public function find(ContactIdentifier $contactIdentifier): ?Contact
            {
                return $this->repository->find($contactIdentifier);
            }

            public function save(Contact $contact): void
            {
                $this->saveCount++;
                $this->repository->save($contact);
            }

            public function saveCount(): int
            {
                return $this->saveCount;
            }
        };
        $this->app->instance(ContactRepositoryInterface::class, $repository);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertServerError();
        self::assertSame(0, $mailService->contactCountWhenSending);
        $this->assertDatabaseHas('contacts', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'title' => 'お問い合わせ',
            'content' => '内容です',
            'status' => 'failed',
            'sent_at' => null,
        ]);
        self::assertSame(1, $repository->saveCount());
    }

    public function test_returns_an_error_and_leaves_no_row_when_contact_save_fails_after_mail_sends(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $repository = new class implements ContactRepositoryInterface
        {
            public int $saveCount = 0;

            public function find(ContactIdentifier $contactIdentifier): ?Contact
            {
                return null;
            }

            public function save(Contact $contact): void
            {
                $this->saveCount++;
                throw new \RuntimeException('Contact persistence failed.');
            }
        };
        $this->app->instance(ContactRepositoryInterface::class, $repository);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertServerError();

        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', 1);
        self::assertSame(1, $repository->saveCount);
        self::assertSame(0, DB::table('contacts')->count());
    }

    public function test_saves_once_after_successful_mail_delivery(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $repository = new class(new ContactRepository) implements ContactRepositoryInterface
        {
            private int $saveCount = 0;

            public function __construct(private ContactRepository $repository) {}

            public function find(ContactIdentifier $contactIdentifier): ?Contact
            {
                return $this->repository->find($contactIdentifier);
            }

            public function save(Contact $contact): void
            {
                $this->saveCount++;
                $this->repository->save($contact);
            }

            public function saveCount(): int
            {
                return $this->saveCount;
            }
        };
        $this->app->instance(ContactRepositoryInterface::class, $repository);

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertNoContent();

        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', 1);
        $this->assertDatabaseHas('contacts', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'title' => 'お問い合わせ',
            'content' => '内容です',
            'status' => 'sent',
        ]);
        self::assertSame(1, $repository->saveCount());
        self::assertNotNull(DB::table('contacts')->value('sent_at'));
    }

    public function test_limits_contact_mail_to_three_per_hour_per_authenticated_account_without_affecting_another_account(): void
    {
        Mail::fake();
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'other-account@example.com');

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
                ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
                ->assertNoContent();
        }

        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertTooManyRequests();
        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', 3);

        $this->withSession(['account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertNoContent();
        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', 4);
    }

    public function test_rejects_unauthenticated_or_nonexistent_accounts_and_ignores_a_spoofed_account_identifier(): void
    {
        Mail::fake();
        $this->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])->assertUnauthorized();
        $this->withSession(['account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'])
            ->postJson('/api/contact-support', ['title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertUnauthorized();

        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');
        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/contact-support', ['account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'title' => 'お問い合わせ', 'content' => '内容です'])
            ->assertNoContent();

        Mail::assertSent('Src\\Contact\\Infrastructure\\Mail\\ContactSupportMail', function ($mail): bool {
            self::assertStringContainsString('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $mail->render());
            self::assertStringNotContainsString('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $mail->render());

            return true;
        });
    }

    private function insertAccount(string $identifier, string $emailAddress): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => 'お問い合わせユーザー',
            'email_address' => $emailAddress,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
