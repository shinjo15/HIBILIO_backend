<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Authentication\Infrastructure\Mail\LoginPasscodeMail;
use Src\Authentication\Infrastructure\Service\RedisLoginPasscodeStateService;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class LoginPasscodeActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);
    }

    public function test_generation_returns_generic_no_content_for_known_and_unknown_email_addresses(): void
    {
        Mail::fake();
        $this->insertAccount('user@example.com');

        $known = $this->postJson('/api/login-passcodes', ['email_address' => 'user@example.com']);

        $known->assertNoContent();
        Mail::assertSent(LoginPasscodeMail::class, fn (LoginPasscodeMail $mail): bool => $mail->hasTo('user@example.com'));
        self::assertNotNull($this->app['session.store']->get('login_passcode_challenge_identifier'));
        self::assertFalse(Schema::hasTable('account_credentials'));

        $this->postJson('/api/login-passcodes', ['email_address' => 'missing@example.com'])->assertNoContent();
        Mail::assertSent(LoginPasscodeMail::class, 1);
        self::assertNull($this->app['session.store']->get('login_passcode_challenge_identifier'));
    }

    public function test_limits_passcode_generation_by_email_address_and_allows_resend_after_cooldown(): void
    {
        Mail::fake();
        $emailAddress = 'rate-limited-login@example.com';
        $this->insertAccount($emailAddress);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login-passcodes', ['email_address' => $emailAddress])->assertNoContent();
        }

        $challengeIdentifier = $this->app['session.store']->get('login_passcode_challenge_identifier');
        $response = $this->postJson('/api/login-passcodes', ['email_address' => $emailAddress]);

        $response->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('retry_after', fn (int $seconds): bool => $seconds > 0);
        Mail::assertSent(LoginPasscodeMail::class, 5);
        self::assertSame($challengeIdentifier, $this->app['session.store']->get('login_passcode_challenge_identifier'));

        $this->travel(10)->minutes();

        $this->postJson('/api/login-passcodes', ['email_address' => $emailAddress])->assertNoContent();
        Mail::assertSent(LoginPasscodeMail::class, 6);
    }

    public function test_verification_rejects_missing_invalid_and_expired_challenges(): void
    {
        Redis::del('login-passcode:challenge:3b5581e9-16df-4879-b7d2-5d88dca6ab87');

        $this->postJson('/api/login-passcodes/verification', ['passcode' => '123456'])->assertUnauthorized();

        $this->withSession(['login_passcode_challenge_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])
            ->postJson('/api/login-passcodes/verification', ['passcode' => '123456'])
            ->assertUnauthorized();
    }

    public function test_verification_establishes_an_account_session_and_deletes_the_challenge(): void
    {
        $challengeIdentifier = new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $this->insertAccount('user@example.com');
        $service = new RedisLoginPasscodeStateService;
        Redis::del('login-passcode:challenge:'.$challengeIdentifier->value());
        $service->register(new LoginPasscodeChallenge(
            $challengeIdentifier,
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new EmailAddress('user@example.com'),
            new LoginPasscodeHash(Hash::make('123456')),
        ));

        $this->withSession(['login_passcode_challenge_identifier' => $challengeIdentifier->value()])
            ->postJson('/api/login-passcodes/verification', ['passcode' => '123456'])
            ->assertNoContent()
            ->assertCookie(LaravelAuthService::PERSISTENT_LOGIN_COOKIE);

        self::assertNull($service->find($challengeIdentifier));
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $this->app['session.store']->get(LaravelAuthService::SESSION_KEY));
        self::assertNull($this->app['session.store']->get('login_passcode_challenge_identifier'));
    }

    public function test_verification_records_mismatch_and_returns_unauthorized(): void
    {
        $challengeIdentifier = new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $service = new RedisLoginPasscodeStateService;
        Redis::del('login-passcode:challenge:'.$challengeIdentifier->value());
        $service->register(new LoginPasscodeChallenge(
            $challengeIdentifier,
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            new EmailAddress('user@example.com'),
            new LoginPasscodeHash(Hash::make('123456')),
        ));

        $this->withSession(['login_passcode_challenge_identifier' => $challengeIdentifier->value()])
            ->postJson('/api/login-passcodes/verification', ['passcode' => '000000'])
            ->assertUnauthorized();

        self::assertNotNull($service->find($challengeIdentifier));
    }

    private function insertAccount(string $emailAddress): void
    {
        $this->app['db']->table('accounts')->insert([
            'account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            'account_name' => 'ログインユーザー',
            'account_bio' => null,
            'email_address' => $emailAddress,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
