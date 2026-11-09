<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class ChangeLikeNotificationsActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    public function test_enables_like_notifications_for_the_authenticated_account(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com', 'available' => true, 'status' => 'active', 'support_notifications_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])
            ->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => true])
            ->assertNoContent();
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'like_notifications_enabled' => true, 'support_notifications_enabled' => true]);
    }

    public function test_disabling_preserves_fields_other_accounts_and_support_setting(): void
    {
        $this->insertAccount(self::ACCOUNT, true, true);
        $other = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($other, true, true);
        $before = (array) DB::table('accounts')->where('account_identifier', self::ACCOUNT)->first();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/like-notifications', [
            'like_notifications_enabled' => false, 'support_notifications_enabled' => false, 'account_identifier' => $other, 'account_name' => 'changed',
        ])->assertNoContent();
        $after = (array) DB::table('accounts')->where('account_identifier', self::ACCOUNT)->first();
        unset($before['like_notifications_enabled'], $before['updated_at'], $after['like_notifications_enabled'], $after['updated_at']);
        self::assertSame($before, $after);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'like_notifications_enabled' => false, 'support_notifications_enabled' => true]);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $other, 'like_notifications_enabled' => true, 'support_notifications_enabled' => true]);
    }

    public function test_rejects_non_boolean_or_missing_values(): void
    {
        $this->insertAccount(self::ACCOUNT, false, true);
        foreach ([[], ['like_notifications_enabled' => null], ['like_notifications_enabled' => 0], ['like_notifications_enabled' => 1], ['like_notifications_enabled' => 'true'], ['like_notifications_enabled' => 'false'], ['like_notifications_enabled' => []]] as $payload) {
            $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/like-notifications', $payload)->assertUnprocessable()->assertJsonValidationErrors('like_notifications_enabled');
        }
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'like_notifications_enabled' => false, 'support_notifications_enabled' => true]);
    }

    public function test_requires_authentication(): void
    {
        $this->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => true])->assertUnauthorized();
    }

    public function test_csrf_protection_requires_a_matching_token(): void
    {
        $this->insertAccount(self::ACCOUNT, false, true);
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $token = str_repeat('a', 40);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT, '_token' => $token])->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => true])->assertStatus(419);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'like_notifications_enabled' => false]);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT, '_token' => $token])->withHeader('X-CSRF-TOKEN', $token)->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => true])->assertNoContent();
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'like_notifications_enabled' => true]);
    }

    public function test_sequential_settings_updates_are_visible_in_my_account(): void
    {
        $this->insertAccount(self::ACCOUNT, false, true);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => true])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => false])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->getJson('/api/my/account')->assertOk()->assertJsonPath('like_notifications_enabled', true)->assertJsonPath('support_notifications_enabled', false);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/like-notifications', ['like_notifications_enabled' => false])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->getJson('/api/my/account')->assertOk()->assertJsonPath('like_notifications_enabled', false)->assertJsonPath('support_notifications_enabled', true);
    }

    private function insertAccount(string $id, bool $like, bool $support): void
    {
        DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => 'User', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active', 'like_notifications_enabled' => $like, 'support_notifications_enabled' => $support, 'created_at' => now(), 'updated_at' => now()]);
    }
}
