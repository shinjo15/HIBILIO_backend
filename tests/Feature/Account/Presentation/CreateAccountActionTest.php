<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Mail\RegistrationPasscodeMail;
use Src\Authentication\Infrastructure\Service\LaravelPendingSocialRegistrationSessionService;
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

    public function test_rejects_a_header_that_exceeds_the_dimension_limit_before_conversion(): void
    {
        $this->insertFavoriteTag();
        session()->put('registration_verified_email_address', 'verified@example.com');
        $payload = $this->validPayload();
        $payload['header_image'] = UploadedFile::fake()->image('header.png', 2560, 1441);

        $this->post('/api/accounts', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('header_image');

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_rejects_an_icon_that_exceeds_the_dimension_limit_before_conversion(): void
    {
        $this->insertFavoriteTag();
        session()->put('registration_verified_email_address', 'verified@example.com');
        $payload = $this->validPayload();
        $payload['icon_image'] = UploadedFile::fake()->image('icon.png', 2049, 2048);

        $this->post('/api/accounts', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('icon_image');

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_returns_unprocessable_when_image_conversion_fails(): void
    {
        $this->insertFavoriteTag();
        session()->put('registration_verified_email_address', 'verified@example.com');
        $this->app->instance(AccountImageConverterServiceInterface::class, new class implements AccountImageConverterServiceInterface
        {
            public function convertToIcon(string $contents): AccountIcon
            {
                throw new InvalidArgumentException('画像をWebPへ変換できません。');
            }

            public function convertToHeader(string $contents): AccountHeader
            {
                throw new InvalidArgumentException('画像をWebPへ変換できません。');
            }
        });
        $payload = $this->validPayload();
        $payload['header_image'] = UploadedFile::fake()->image('header.png', 640, 320);

        $this->post('/api/accounts', $payload)->assertUnprocessable();

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_creates_and_links_an_account_from_a_pending_social_registration_without_a_passcode(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        session()->put(LaravelPendingSocialRegistrationSessionService::SESSION_KEY, [
            'provider' => SocialLoginProvider::APPLE->value,
            'provider_user_identifier' => 'apple-user',
            'email_address' => 'private@example.com',
        ]);

        $this->postJson('/api/accounts', $this->validPayload())->assertCreated();

        $this->assertDatabaseHas('accounts', ['email_address' => 'private@example.com']);
        $this->assertDatabaseHas('social_login_connections', [
            'provider' => 'apple',
            'provider_user_identifier' => 'apple-user',
        ]);
        self::assertNotNull(session('account_identifier'));
        self::assertNull(session(LaravelPendingSocialRegistrationSessionService::SESSION_KEY));
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
