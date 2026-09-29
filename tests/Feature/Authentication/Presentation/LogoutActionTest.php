<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LogoutActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_revokes_only_the_current_browser_token_and_invalidates_the_session(): void
    {
        $account = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';
        $this->app['db']->table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'public', 'ui_mode' => 'system', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'cccccccccccccccccccccccccccccccc'] as $selector) {
            $this->app['db']->table('persistent_login_tokens')->insert(['selector' => $selector, 'account_identifier' => $account, 'validator_hash' => Hash::make(str_repeat('b', 64)), 'expires_at' => new DateTimeImmutable('+1 day'), 'created_at' => now(), 'updated_at' => now()]);
        }

        $response = $this->withSession(['account_identifier' => $account])->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))->post('/api/logout');

        $response->assertNoContent()->assertCookie('persistent_login');
        self::assertDatabaseMissing('persistent_login_tokens', ['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        self::assertDatabaseHas('persistent_login_tokens', ['selector' => 'cccccccccccccccccccccccccccccccc']);
        $this->getJson('/api/my/account')->assertUnauthorized();
        $this->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))->post('/api/persistent-login/restore')->assertUnauthorized();
    }

    public function test_malformed_cookie_does_not_revoke_an_unrelated_token(): void
    {
        $this->app['db']->table('accounts')->insert(['account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'account_name' => 'User', 'email_address' => 'user@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'public', 'ui_mode' => 'system', 'created_at' => now(), 'updated_at' => now()]);
        $this->app['db']->table('persistent_login_tokens')->insert(['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'validator_hash' => Hash::make(str_repeat('b', 64)), 'expires_at' => new DateTimeImmutable('+1 day'), 'created_at' => now(), 'updated_at' => now()]);

        $this->withCookie('persistent_login', 'malformed')->post('/api/logout')->assertNoContent();

        self::assertDatabaseHas('persistent_login_tokens', ['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
    }
}
