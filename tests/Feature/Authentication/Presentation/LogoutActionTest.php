<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class LogoutActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_deletes_the_persistent_login_token_clears_cookie_and_prevents_restoration(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $selector = 'persistent-login-selector';
        $validator = 'persistent-login-validator';
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => 'ログインアカウント',
            'email_address' => 'logout@example.com',
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('persistent_login_tokens')->insert([
            'selector' => $selector,
            'account_identifier' => $accountIdentifier,
            'validator_hash' => Hash::make($validator),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withCredentials()
            ->withSession([LaravelAuthService::SESSION_KEY => $accountIdentifier])
            ->withCookie(LaravelAuthService::PERSISTENT_LOGIN_COOKIE, $selector.'.'.$validator)
            ->postJson('/api/logout');

        $response->assertNoContent();
        self::assertFalse(DB::table('persistent_login_tokens')->where('selector', $selector)->exists());
        $clearedCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie): bool => $cookie->getName() === LaravelAuthService::PERSISTENT_LOGIN_COOKIE);
        self::assertNotNull($clearedCookie);
        self::assertSame(0, $clearedCookie->getMaxAge());

        $this->withCredentials()
            ->withCookie(LaravelAuthService::PERSISTENT_LOGIN_COOKIE, $selector.'.'.$validator)
            ->getJson('/api/my/account')
            ->assertUnauthorized();
    }
}
