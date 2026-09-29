<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Infrastructure\Mail\LoginPasscodeMail;
use Src\Authentication\Infrastructure\Service\LaravelPasscodeSessionService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeGeneratorService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeHashService;
use Src\Authentication\Infrastructure\Service\LoginPasscodeMailService;
use Tests\TestCase;

final class LoginPasscodeInfrastructureTest extends TestCase
{
    public function test_generator_returns_a_six_digit_numeric_passcode(): void
    {
        self::assertMatchesRegularExpression('/^\d{6}$/D', (new LoginPasscodeGeneratorService)->generate());
    }

    public function test_hash_service_hashes_and_matches_passcodes(): void
    {
        $passcode = new LoginPasscode('123456');
        $hashService = $this->app->make(LoginPasscodeHashService::class);
        $hash = $hashService->hash($passcode);

        self::assertTrue(Hash::check('123456', $hash->value()));
        self::assertTrue($hashService->matches($passcode, $hash));
    }

    public function test_mail_service_sends_plain_japanese_passcode_email(): void
    {
        Mail::fake();
        (new LoginPasscodeMailService)->send(new EmailAddress('user@example.com'), new LoginPasscode('123456'));
        Mail::assertSent(LoginPasscodeMail::class, function (LoginPasscodeMail $mail): bool {
            self::assertTrue($mail->hasTo('user@example.com'));
            self::assertStringContainsString('123456', $mail->render());
            self::assertStringContainsString('HIBILIO', $mail->render());
            self::assertSame('HIBILIO ログインパスコード', $mail->subject);

            return true;
        });
    }

    public function test_session_service_stores_and_clears_only_the_challenge_identifier(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        $service = new LaravelPasscodeSessionService($request);
        $service->setChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $service->challengeIdentifier());
        $service->clearChallengeIdentifier();
        $this->expectException(\RuntimeException::class);
        $service->challengeIdentifier();
    }
}
