<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Src\Authentication\Infrastructure\Mail\RegistrationPasscodeMail;
use Tests\TestCase;

final class CreateAccountActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_account_with_the_session_verified_email_address(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        $this->verifyRegistrationEmailAddress('verified@example.com');
        $payload = $this->validPayload();
        $payload['email_address'] = 'untrusted@example.com';

        $response = $this->postJson('/api/accounts', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('accounts', ['email_address' => 'verified@example.com', 'account_name' => '朝活ユーザー']);
        $this->assertDatabaseMissing('accounts', ['email_address' => 'untrusted@example.com']);
        self::assertNull($this->app['session.store']->get('registration_verified_email_address'));
    }

    public function test_rejects_account_creation_without_a_verified_registration_email_address(): void
    {
        $this->insertFavoriteTag();

        $this->postJson('/api/accounts', $this->validPayload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_rejects_account_creation_after_an_incorrect_registration_passcode(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        $this->postJson('/api/registration-passcodes', ['email_address' => 'verified@example.com'])
            ->assertNoContent();

        $this->postJson('/api/registration-passcodes/verification', ['passcode' => '000000'])
            ->assertUnauthorized();
        $this->postJson('/api/accounts', $this->validPayload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_cannot_reuse_a_verified_registration_email_address_after_account_creation(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        $this->verifyRegistrationEmailAddress('verified@example.com');

        $this->postJson('/api/accounts', $this->validPayload())->assertCreated();
        $this->postJson('/api/accounts', $this->validPayload())->assertUnauthorized();

        $this->assertDatabaseCount('accounts', 1);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'account_name' => '朝活ユーザー',
            'account_bio' => '朝の時間を大切にしています。',
            'social_links' => [['social_type' => 'x', 'social_url' => 'https://x.com/example']],
            'favorite_tag_identifiers' => ['b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'],
        ];
    }

    private function verifyRegistrationEmailAddress(string $emailAddress): void
    {
        $passcode = null;
        $this->postJson('/api/registration-passcodes', ['email_address' => $emailAddress])
            ->assertNoContent();
        Mail::assertSent(RegistrationPasscodeMail::class, function (RegistrationPasscodeMail $mail) use (&$passcode): bool {
            $passcode = $mail->passcode;

            return true;
        });
        $this->postJson('/api/registration-passcodes/verification', ['passcode' => $passcode])
            ->assertNoContent();
    }

    private function insertFavoriteTag(): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'tag_name' => '朝活',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
