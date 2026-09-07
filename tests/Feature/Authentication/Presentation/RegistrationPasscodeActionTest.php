<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Src\Authentication\Infrastructure\Mail\RegistrationPasscodeMail;
use Tests\TestCase;

final class RegistrationPasscodeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_a_registration_passcode_to_an_unregistered_email_address(): void
    {
        Mail::fake();

        $this->postJson('/api/registration-passcodes', ['email_address' => 'new@example.com'])
            ->assertNoContent();

        Mail::assertSent(RegistrationPasscodeMail::class, fn (RegistrationPasscodeMail $mail): bool => $mail->hasTo('new@example.com'));
        self::assertIsString($this->app['session.store']->get('registration_passcode_challenge_identifier'));
    }

    public function test_verifies_a_registration_passcode_and_stores_its_email_address_in_the_session(): void
    {
        Mail::fake();
        $passcode = null;

        $this->postJson('/api/registration-passcodes', ['email_address' => 'new@example.com'])
            ->assertNoContent();
        Mail::assertSent(RegistrationPasscodeMail::class, function (RegistrationPasscodeMail $mail) use (&$passcode): bool {
            $passcode = $mail->passcode;

            return true;
        });

        $this->postJson('/api/registration-passcodes/verification', ['passcode' => $passcode])
            ->assertNoContent();

        self::assertSame('new@example.com', $this->app['session.store']->get('registration_verified_email_address'));
        self::assertNull($this->app['session.store']->get('registration_passcode_challenge_identifier'));
    }

    public function test_rejects_a_registration_passcode_request_for_an_existing_account_email_address(): void
    {
        Mail::fake();
        $this->insertAccount('existing@example.com');

        $this->postJson('/api/registration-passcodes', ['email_address' => 'existing@example.com'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'このメールアドレスはすでに登録されています。']);

        Mail::assertNothingSent();
        self::assertNull($this->app['session.store']->get('registration_passcode_challenge_identifier'));
    }

    private function insertAccount(string $emailAddress): void
    {
        $this->app['db']->table('accounts')->insert([
            'account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            'account_name' => '既存ユーザー',
            'account_bio' => null,
            'email_address' => $emailAddress,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
