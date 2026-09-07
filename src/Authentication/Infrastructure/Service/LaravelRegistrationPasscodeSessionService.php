<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Http\Request;
use RuntimeException;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;

final readonly class LaravelRegistrationPasscodeSessionService implements RegistrationPasscodeSessionServiceInterface
{
    public const CHALLENGE_SESSION_KEY = 'registration_passcode_challenge_identifier';

    public const VERIFIED_EMAIL_ADDRESS_SESSION_KEY = 'registration_verified_email_address';

    public function __construct(private Request $request) {}

    public function challengeIdentifier(): string
    {
        $challengeIdentifier = $this->request->session()->get(self::CHALLENGE_SESSION_KEY);
        if (! is_string($challengeIdentifier) || $challengeIdentifier === '') {
            throw new RuntimeException('セッションに登録パスコードチャレンジ識別子がありません。');
        }

        return $challengeIdentifier;
    }

    public function setChallengeIdentifier(string $challengeIdentifier): void
    {
        $this->request->session()->put(self::CHALLENGE_SESSION_KEY, $challengeIdentifier);
    }

    public function clearChallengeIdentifier(): void
    {
        $this->request->session()->forget(self::CHALLENGE_SESSION_KEY);
    }

    public function verifiedEmailAddress(): EmailAddress
    {
        $emailAddress = $this->request->session()->get(self::VERIFIED_EMAIL_ADDRESS_SESSION_KEY);
        if (! is_string($emailAddress) || $emailAddress === '') {
            throw new RuntimeException('セッションに認証済み登録メールアドレスがありません。');
        }

        return new EmailAddress($emailAddress);
    }

    public function setVerifiedEmailAddress(EmailAddress $emailAddress): void
    {
        $this->request->session()->put(self::VERIFIED_EMAIL_ADDRESS_SESSION_KEY, $emailAddress->value());
    }

    public function clearVerifiedEmailAddress(): void
    {
        $this->request->session()->forget(self::VERIFIED_EMAIL_ADDRESS_SESSION_KEY);
    }
}
