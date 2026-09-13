<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Http\Request;
use Src\Authentication\Application\Service\PendingSocialRegistrationSessionServiceInterface;
use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final readonly class LaravelPendingSocialRegistrationSessionService implements PendingSocialRegistrationSessionServiceInterface
{
    public const SESSION_KEY = 'pending_social_registration';

    public function __construct(private Request $request) {}

    public function pending(): ?PendingSocialRegistration
    {
        $data = $this->request->session()->get(self::SESSION_KEY);
        if (! is_array($data)) {
            return null;
        }

        if (
            ! is_string($data['provider'] ?? null)
            || ! is_string($data['provider_user_identifier'] ?? null)
            || ! is_string($data['email_address'] ?? null)
        ) {
            return null;
        }

        try {
            return new PendingSocialRegistration(
                SocialLoginProvider::from($data['provider']),
                $data['provider_user_identifier'],
                $data['email_address'],
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function set(PendingSocialRegistration $registration): void
    {
        $this->request->session()->put(self::SESSION_KEY, [
            'provider' => $registration->provider()->value,
            'provider_user_identifier' => $registration->providerUserIdentifier(),
            'email_address' => $registration->emailAddress()->value(),
        ]);
    }

    public function clear(): void
    {
        $this->request->session()->forget(self::SESSION_KEY);
    }
}
