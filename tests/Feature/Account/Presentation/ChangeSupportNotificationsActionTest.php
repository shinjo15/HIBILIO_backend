<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Command\ChangeAccountStatus\ChangeAccountStatusInput;
use Src\Account\Application\Usecase\Command\ChangeAccountStatus\ChangeAccountStatusInterface;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class ChangeSupportNotificationsActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    public function test_enables_support_notifications_for_the_authenticated_account(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com', 'available' => true, 'status' => 'active', 'like_notifications_enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])
            ->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])
            ->assertNoContent();
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => true, 'like_notifications_enabled' => true]);
    }

    public function test_disables_only_own_setting_and_preserves_other_fields(): void
    {
        $this->insertAccount(self::ACCOUNT, true, true);
        $other = '22222222-2222-4222-8222-222222222222';
        $this->insertAccount($other, true, true);
        $before = (array) DB::table('accounts')->where('account_identifier', self::ACCOUNT)->first();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/support-notifications', [
            'support_notifications_enabled' => false,
            'account_identifier' => $other,
            'like_notifications_enabled' => false,
            'account_name' => 'changed',
        ])->assertNoContent();
        $after = (array) DB::table('accounts')->where('account_identifier', self::ACCOUNT)->first();
        unset($before['support_notifications_enabled'], $before['updated_at'], $after['support_notifications_enabled'], $after['updated_at']);
        self::assertSame($before, $after);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => false, 'like_notifications_enabled' => true]);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $other, 'support_notifications_enabled' => true, 'like_notifications_enabled' => true]);
    }

    public function test_rejects_invalid_or_missing_boolean(): void
    {
        $this->insertAccount(self::ACCOUNT, false, true);
        foreach ([[], ['support_notifications_enabled' => null], ['support_notifications_enabled' => 0], ['support_notifications_enabled' => 1], ['support_notifications_enabled' => 'true'], ['support_notifications_enabled' => []]] as $payload) {
            $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/support-notifications', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('support_notifications_enabled');
        }
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => false, 'like_notifications_enabled' => true]);
    }

    public function test_requires_authentication(): void
    {
        $this->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertUnauthorized();
    }

    public function test_disabled_or_banned_account_cannot_change_setting(): void
    {
        $this->insertAccount(self::ACCOUNT, false, true);
        foreach ([['available' => false], ['available' => true, 'status' => 'permanently_banned']] as $changes) {
            DB::table('accounts')->where('account_identifier', self::ACCOUNT)->update($changes);
            $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])
                ->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertUnauthorized();
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => false, 'like_notifications_enabled' => true, ...$changes]);
        }
    }

    public function test_returns_not_found_when_authenticated_account_is_missing(): void
    {
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return '11111111-1111-4111-8111-111111111111';
            }
        });
        $this->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertNotFound();
    }

    public function test_web_csrf_protection_requires_a_matching_token(): void
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
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT, '_token' => $token])
            ->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertStatus(419);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => false]);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT, '_token' => $token])
            ->withHeader('X-CSRF-TOKEN', $token)->patchJson('/api/my/account/support-notifications', ['support_notifications_enabled' => true])->assertNoContent();
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => true]);
    }

    public function test_other_account_operations_preserve_both_notification_settings(): void
    {
        $this->insertAccount(self::ACCOUNT, true, true);
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/ui-mode', ['ui_mode' => 'dark'])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account/visibility', ['visibility' => 'private'])->assertNoContent();
        $this->withSession([LaravelAuthService::SESSION_KEY => self::ACCOUNT])->patchJson('/api/my/account', ['account_name' => 'Updated'])->assertNoContent();
        $this->app->make(ChangeAccountStatusInterface::class)->execute(new ChangeAccountStatusInput(new AccountIdentifier(self::ACCOUNT), AccountStatus::TEMPORARILY_BANNED));
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'support_notifications_enabled' => true, 'like_notifications_enabled' => true, 'account_name' => 'Updated', 'ui_mode' => 'dark', 'visibility' => 'private', 'status' => 'temporarily_banned']);
    }

    private function insertAccount(string $id, bool $support, bool $like): void
    {
        DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => 'User', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active', 'support_notifications_enabled' => $support, 'like_notifications_enabled' => $like, 'created_at' => now(), 'updated_at' => now()]);
    }
}
