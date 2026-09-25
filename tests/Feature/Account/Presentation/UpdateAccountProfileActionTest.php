<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class UpdateAccountProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_only_the_submitted_profile_field_and_does_not_change_the_email_address(): void
    {
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patchJson('/api/my/account', [
            'account_name' => '更新後ユーザー',
            'email_address' => 'changed@example.com',
        ])->assertNoContent();

        $this->assertDatabaseHas('accounts', [
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'account_name' => '更新後ユーザー',
            'account_bio' => '既存の自己紹介',
            'email_address' => 'original@example.com',
        ]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->patchJson('/api/my/account', ['account_name' => '更新後ユーザー'])
            ->assertUnauthorized();
    }

    public function test_removes_a_bio_and_replaces_submitted_profile_lists(): void
    {
        $this->insertAccount();
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->insertSocialLink();
        $this->insertFavoriteTag();
        $this->authenticateAsAccount();

        $this->patchJson('/api/my/account', [
            'account_bio' => null,
            'social_links' => [],
            'favorite_tag_identifiers' => [],
        ])->assertNoContent();

        $this->assertDatabaseHas('accounts', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'account_bio' => null]);
        $this->assertDatabaseCount('account_social_links', 0);
        $this->assertDatabaseCount('favorite_tags', 0);
    }

    public function test_reconciles_submitted_profile_lists_without_recreating_the_account(): void
    {
        $this->insertAccount();
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->insertSocialLink();
        $this->insertFavoriteTag();
        $this->authenticateAsAccount();

        $this->patchJson('/api/my/account', [
            'social_links' => [['social_type' => 'instagram', 'social_url' => 'https://instagram.com/example']],
            'favorite_tag_identifiers' => ['bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'],
        ])->assertNoContent();

        $this->assertDatabaseHas('accounts', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'email_address' => 'original@example.com']);
        $this->assertDatabaseHas('account_social_links', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'type' => 'instagram', 'url' => 'https://instagram.com/example', 'position' => 0]);
        $this->assertDatabaseMissing('account_social_links', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'type' => 'x']);
        $this->assertDatabaseHas('favorite_tags', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'tag_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb']);
        $this->assertDatabaseMissing('favorite_tags', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);
    }

    public function test_rejects_an_icon_file_with_an_icon_deletion_request(): void
    {
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patch('/api/my/account', [
            'icon_image' => UploadedFile::fake()->image('icon.png', 128, 128),
            'icon_image_deleted' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('icon_image');
    }

    public function test_rejects_an_icon_that_exceeds_the_dimension_limit_before_conversion(): void
    {
        Storage::fake('account_images');
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patch('/api/my/account', [
            'icon_image' => UploadedFile::fake()->image('icon.png', 1025, 1024),
        ])->assertUnprocessable()->assertJsonValidationErrors('icon_image');

        Storage::disk('account_images')->assertMissing('accounts/11111111-1111-4111-8111-111111111111/icon/icon.webp');
    }

    public function test_rejects_a_header_that_exceeds_the_dimension_limit_before_conversion(): void
    {
        Storage::fake('account_images');
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patch('/api/my/account', [
            'header_image' => UploadedFile::fake()->image('header.png', 1920, 1081),
        ])->assertUnprocessable()->assertJsonValidationErrors('header_image');

        Storage::disk('account_images')->assertMissing('accounts/11111111-1111-4111-8111-111111111111/header/header.webp');
    }

    public function test_converts_and_stores_images_within_the_dimension_limits(): void
    {
        Storage::fake('account_images');
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patch('/api/my/account', [
            'icon_image' => UploadedFile::fake()->image('icon.png', 128, 128),
            'header_image' => UploadedFile::fake()->image('header.png', 640, 320),
        ])->assertNoContent();

        Storage::disk('account_images')->assertExists('accounts/11111111-1111-4111-8111-111111111111/icon/icon.webp');
        Storage::disk('account_images')->assertExists('accounts/11111111-1111-4111-8111-111111111111/header/header.webp');
        self::assertSame('image/webp', getimagesizefromstring(Storage::disk('account_images')->get('accounts/11111111-1111-4111-8111-111111111111/icon/icon.webp'))['mime']);
        self::assertSame('image/webp', getimagesizefromstring(Storage::disk('account_images')->get('accounts/11111111-1111-4111-8111-111111111111/header/header.webp'))['mime']);
    }

    public function test_returns_unprocessable_when_image_conversion_fails(): void
    {
        $this->insertAccount();
        $this->authenticateAsAccount();
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

        $this->patch('/api/my/account', ['icon_image' => UploadedFile::fake()->image('icon.png', 128, 128)])
            ->assertUnprocessable();
    }

    public function test_deletes_the_icon_when_requested(): void
    {
        Storage::fake('account_images');
        Storage::disk('account_images')->put('accounts/11111111-1111-4111-8111-111111111111/icon/icon.webp', 'existing icon');
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patchJson('/api/my/account', ['icon_image_deleted' => true])->assertNoContent();

        Storage::disk('account_images')->assertMissing('accounts/11111111-1111-4111-8111-111111111111/icon/icon.webp');
    }

    private function insertAccount(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'account_name' => '既存ユーザー',
            'account_bio' => '既存の自己紹介',
            'email_address' => 'original@example.com',
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authenticateAsAccount(): void
    {
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function logout(): void {}

            public function accountIdentifier(): string
            {
                return '11111111-1111-4111-8111-111111111111';
            }
        });
    }

    private function insertTag(string $identifier): void
    {
        DB::table('tags')->insert(['tag_identifier' => $identifier, 'tag_name' => '朝活', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertSocialLink(): void
    {
        DB::table('account_social_links')->insert(['account_identifier' => '11111111-1111-4111-8111-111111111111', 'type' => 'x', 'url' => 'https://x.com/example', 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertFavoriteTag(): void
    {
        DB::table('favorite_tags')->insert(['account_identifier' => '11111111-1111-4111-8111-111111111111', 'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'created_at' => now(), 'updated_at' => now()]);
    }
}
