<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LaravelAuthService implements AuthServiceInterface
{
    public const SESSION_KEY = 'account_identifier';

    public const PERSISTENT_LOGIN_COOKIE = 'hibilio_persistent_login';

    private const PERSISTENT_LOGIN_MINUTES = 60 * 24 * 30;

    public function __construct(
        private Request $request,
        private PersistentLoginTokenRepositoryInterface $persistentLoginTokens,
    ) {}

    public function login(AccountIdentifier $accountIdentifier): void
    {
        $this->request->session()->regenerate();
        $this->request->session()->put(self::SESSION_KEY, $accountIdentifier->value());
        $this->issuePersistentLoginToken(
            $accountIdentifier,
            (new DateTimeImmutable)->modify('+30 days'),
            self::PERSISTENT_LOGIN_MINUTES,
        );
    }

    public function accountIdentifier(): ?string
    {
        $accountIdentifier = $this->request->session()->get(self::SESSION_KEY);

        if (! is_string($accountIdentifier) || $accountIdentifier === '') {
            return $this->restorePersistentLogin();
        }

        $isAuthenticatedAccount = DB::table('accounts')
            ->where('account_identifier', $accountIdentifier)
            ->where('available', true)
            ->where('status', 'active')
            ->exists();

        if (! $isAuthenticatedAccount) {
            $this->request->session()->forget(self::SESSION_KEY);
            $this->deleteCurrentPersistentLoginToken();
            $this->clearPersistentLoginCookie();

            return null;
        }

        return $accountIdentifier;
    }

    public function logout(): void
    {
        $this->deleteCurrentPersistentLoginToken();
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();
        $this->clearPersistentLoginCookie();
    }

    private function restorePersistentLogin(): ?string
    {
        [$selector, $validator] = $this->persistentLoginCookieParts();
        if ($selector === null || $validator === null) {
            $this->clearPersistentLoginCookie();

            return null;
        }

        $token = $this->persistentLoginTokens->find($selector);
        if (
            $token === null
            || $token->isExpired(new DateTimeImmutable)
            || ! Hash::check($validator, $token->validatorHash())
            || ! $this->isActiveAccount($token->accountIdentifier()->value())
        ) {
            $this->persistentLoginTokens->delete($selector);
            $this->clearPersistentLoginCookie();

            return null;
        }

        $this->persistentLoginTokens->delete($selector);
        $this->request->session()->regenerate();
        $this->request->session()->put(self::SESSION_KEY, $token->accountIdentifier()->value());
        $this->issuePersistentLoginToken(
            $token->accountIdentifier(),
            $token->expiresAt(),
            $this->remainingPersistentLoginMinutes($token->expiresAt()),
        );

        return $token->accountIdentifier()->value();
    }

    private function issuePersistentLoginToken(
        AccountIdentifier $accountIdentifier,
        DateTimeImmutable $expiresAt,
        int $cookieMinutes,
    ): void {
        $selector = bin2hex(random_bytes(32));
        $validator = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->persistentLoginTokens->save(new PersistentLoginToken(
            $selector,
            $accountIdentifier,
            Hash::make($validator),
            $expiresAt,
        ));
        Cookie::queue(Cookie::make(
            self::PERSISTENT_LOGIN_COOKIE,
            $selector.'.'.$validator,
            $cookieMinutes,
            '/',
            null,
            true,
            true,
            false,
            'lax',
        ));
    }

    private function remainingPersistentLoginMinutes(DateTimeImmutable $expiresAt): int
    {
        $remainingSeconds = $expiresAt->getTimestamp() - (new DateTimeImmutable)->getTimestamp();

        return max(0, intdiv($remainingSeconds + 59, 60));
    }

    private function deleteCurrentPersistentLoginToken(): void
    {
        [$selector] = $this->persistentLoginCookieParts();
        if ($selector !== null) {
            $this->persistentLoginTokens->delete($selector);
        }
    }

    /** @return array{?string, ?string} */
    private function persistentLoginCookieParts(): array
    {
        $cookie = $this->request->cookie(self::PERSISTENT_LOGIN_COOKIE);
        if (! is_string($cookie)) {
            return [null, null];
        }

        $parts = explode('.', $cookie, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private function clearPersistentLoginCookie(): void
    {
        Cookie::queue(Cookie::make(
            self::PERSISTENT_LOGIN_COOKIE,
            '',
            -1,
            '/',
            null,
            true,
            true,
            false,
            'lax',
        ));
    }

    private function isActiveAccount(string $accountIdentifier): bool
    {
        return DB::table('accounts')
            ->where('account_identifier', $accountIdentifier)
            ->where('available', true)
            ->where('status', 'active')
            ->exists();
    }
}
