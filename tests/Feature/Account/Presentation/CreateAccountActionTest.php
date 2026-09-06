<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Src\Account\Infrastructure\Mail\AccountRegistrationMail;
use Tests\TestCase;

final class CreateAccountActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_an_account_without_a_passcode_and_returns_no_credential(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();

        $response = $this->postJson('/api/accounts', $this->validPayload());

        $response->assertCreated();
        self::assertSame('', $response->getContent());
        self::assertStringNotContainsString('passcode', $response->getContent());
        $this->assertDatabaseHas('accounts', ['email_address' => 'user@example.com', 'account_name' => '朝活ユーザー']);
        $accountIdentifier = $this->app['db']->table('accounts')->where('email_address', 'user@example.com')->value('account_identifier');
        $this->assertDatabaseHas('account_social_links', [
            'account_identifier' => $accountIdentifier,
            'type' => 'x',
            'url' => 'https://x.com/example',
            'position' => 0,
        ]);
        $this->assertDatabaseHas('favorite_tags', [
            'account_identifier' => $accountIdentifier,
            'tag_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
        ]);
        self::assertFalse(Schema::hasTable('account_credentials'));
        Mail::assertSent(AccountRegistrationMail::class, fn (AccountRegistrationMail $mail): bool => $mail->hasTo('user@example.com'));
    }

    public function test_ignores_a_passcode_field(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        $payload = $this->validPayload();
        $payload['passcode'] = ['temporary', 'value'];

        $this->postJson('/api/accounts', $payload)->assertCreated();

        self::assertFalse(Schema::hasTable('account_credentials'));
    }

    public function test_registers_an_account_when_social_links_and_favorite_tags_are_empty(): void
    {
        Mail::fake();
        $payload = $this->validPayload();
        $payload['social_links'] = [];
        $payload['favorite_tag_identifiers'] = [];

        $response = $this->postJson('/api/accounts', $payload);

        $response->assertCreated();
        $accountIdentifier = $this->app['db']->table('accounts')
            ->where('email_address', 'user@example.com')
            ->value('account_identifier');
        $this->assertDatabaseCount('account_social_links', 0);
        $this->assertDatabaseCount('favorite_tags', 0);
        self::assertNotNull($accountIdentifier);
    }

    public function test_requires_social_links_and_favorite_tags_to_be_present(): void
    {
        $this->postJson('/api/accounts', [
            'account_name' => '朝活ユーザー',
            'email_address' => 'user@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'social_links',
            'favorite_tag_identifiers',
        ]);
    }

    public function test_uploads_optional_account_images_as_webp(): void
    {
        Mail::fake();
        Storage::fake('account_images');
        $this->insertFavoriteTag();
        $payload = $this->validPayload();
        $payload['icon_image'] = UploadedFile::fake()->image('icon.png', 128, 128);
        $payload['header_image'] = UploadedFile::fake()->image('header.png', 640, 320);

        $this->post('/api/accounts', $payload)->assertCreated();

        $accountIdentifier = $this->app['db']->table('accounts')
            ->where('email_address', 'user@example.com')
            ->value('account_identifier');

        Storage::disk('account_images')->assertExists("accounts/{$accountIdentifier}/icon/icon.webp");
        Storage::disk('account_images')->assertExists("accounts/{$accountIdentifier}/header/header.webp");
    }

    public function test_returns_a_japanese_validation_error_for_a_duplicate_email_address(): void
    {
        Mail::fake();
        $this->insertFavoriteTag();
        $this->postJson('/api/accounts', $this->validPayload())->assertCreated();

        $this->postJson('/api/accounts', $this->validPayload())->assertUnprocessable()->assertJson(['message' => 'このメールアドレスはすでに登録されています。']);
    }

    public function test_validates_the_required_account_registration_input(): void
    {
        $this->postJson('/api/accounts', [
            'account_name' => '', 'email_address' => 'invalid',
            'social_links' => [['social_type' => 'invalid', 'social_url' => 'invalid']],
            'favorite_tag_identifiers' => ['invalid'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['account_name', 'email_address', 'social_links.0.social_type', 'social_links.0.social_url', 'favorite_tag_identifiers.0']);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return ['account_name' => '朝活ユーザー', 'account_bio' => '朝の時間を大切にしています。', 'email_address' => 'user@example.com', 'social_links' => [['social_type' => 'x', 'social_url' => 'https://x.com/example']], 'favorite_tag_identifiers' => ['b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5']];
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
