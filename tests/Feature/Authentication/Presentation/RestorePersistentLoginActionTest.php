<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use DateTimeImmutable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RestorePersistentLoginActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028';

    public function test_restores_session_and_rotates_a_valid_token_without_extending_expiry(): void
    {
        $this->insertAccount();
        $expiresAt = new DateTimeImmutable('+10 days');
        $this->insertToken('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $expiresAt);

        $response = $this->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))->post('/api/persistent-login/restore');

        $response->assertNoContent()->assertCookie('persistent_login');
        self::assertSame(self::ACCOUNT, $this->app['session.store']->get('account_identifier'));
        self::assertDatabaseMissing('persistent_login_tokens', ['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        $cookie = $response->getCookie('persistent_login', false);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertSame('', $response->getContent());
        self::assertStringNotContainsString('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', (string) $response->headers->get('Location'));
        self::assertStringNotContainsString(str_repeat('b', 64), $response->getContent());
        self::assertSame($expiresAt->getTimestamp(), $cookie->getExpiresTime());
        self::assertDatabaseCount('persistent_login_tokens', 1);
        $rotatedCookie = CookieValuePrefix::remove($this->app['encrypter']->decrypt(urldecode($cookie->getValue()), false));
        [$selector, $rawValidator] = explode(':', $rotatedCookie, 2);
        $rotated = $this->app['db']->table('persistent_login_tokens')->where('selector', $selector)->first();
        self::assertNotNull($rotated);
        self::assertTrue(Hash::check($rawValidator, $rotated->validator_hash));
        self::assertSame($expiresAt->getTimestamp(), (new DateTimeImmutable($rotated->expires_at))->getTimestamp());

        $this->withSession(['account_identifier' => null])
            ->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))
            ->post('/api/persistent-login/restore')
            ->assertUnauthorized();
        self::assertNull($this->app['session.store']->get('account_identifier'));
        self::assertDatabaseHas('persistent_login_tokens', ['selector' => $selector]);
    }

    public function test_rejects_missing_or_malformed_cookie_and_clears_it(): void
    {
        $this->post('/api/persistent-login/restore')->assertUnauthorized()->assertCookie('persistent_login');
        $this->withCookie('persistent_login', 'not-a-token')->post('/api/persistent-login/restore')->assertUnauthorized()->assertCookie('persistent_login');
    }

    public function test_rejects_and_revokes_expired_or_disabled_account_tokens(): void
    {
        $this->insertAccount();
        $this->insertToken('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', new DateTimeImmutable('-1 second'));
        $this->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))->post('/api/persistent-login/restore')->assertUnauthorized();
        self::assertDatabaseMissing('persistent_login_tokens', ['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);

        $this->insertToken('cccccccccccccccccccccccccccccccc', new DateTimeImmutable('+1 day'));
        $this->app['db']->table('accounts')->where('account_identifier', self::ACCOUNT)->update(['available' => false]);
        $this->withCookie('persistent_login', 'cccccccccccccccccccccccccccccccc:'.str_repeat('b', 64))->post('/api/persistent-login/restore')->assertUnauthorized();
        self::assertDatabaseMissing('persistent_login_tokens', ['selector' => 'cccccccccccccccccccccccccccccccc']);

        $this->insertToken('dddddddddddddddddddddddddddddddd', new DateTimeImmutable('+1 day'));
        $this->app['db']->table('accounts')->where('account_identifier', self::ACCOUNT)->update(['available' => true, 'status' => 'permanently_banned']);
        $this->withCookie('persistent_login', 'dddddddddddddddddddddddddddddddd:'.str_repeat('b', 64))->post('/api/persistent-login/restore')->assertUnauthorized();
        self::assertDatabaseMissing('persistent_login_tokens', ['selector' => 'dddddddddddddddddddddddddddddddd']);
    }

    public function test_keeps_an_existing_authenticated_session_untouched(): void
    {
        $this->insertAccount();
        $this->insertToken('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', new DateTimeImmutable('+1 day'));

        $response = $this->withSession(['account_identifier' => self::ACCOUNT])->withCookie('persistent_login', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa:'.str_repeat('b', 64))->post('/api/persistent-login/restore');

        $response->assertNoContent()->assertCookieMissing('persistent_login');
        self::assertDatabaseHas('persistent_login_tokens', ['selector' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        self::assertSame(self::ACCOUNT, $this->app['session.store']->get('account_identifier'));
    }

    private function insertAccount(): void
    {
        $this->app['db']->table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'public', 'ui_mode' => 'system', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertToken(string $selector, DateTimeImmutable $expiresAt): void
    {
        $this->app['db']->table('persistent_login_tokens')->insert(['selector' => $selector, 'account_identifier' => self::ACCOUNT, 'validator_hash' => Hash::make(str_repeat('b', 64)), 'expires_at' => $expiresAt, 'created_at' => now(), 'updated_at' => now()]);
    }
}
