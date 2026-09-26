<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;

final readonly class LaravelPersistentLoginCookieService implements PersistentLoginCookieServiceInterface
{
    public const COOKIE_NAME = 'hibilio_persistent_login';

    public function __construct(private Request $request) {}

    public function value(): ?string
    {
        $value = $this->request->cookie(self::COOKIE_NAME);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function queue(string $value, int $minutes): void
    {
        Cookie::queue(Cookie::make(self::COOKIE_NAME, $value, $minutes, '/', null, true, true, false, 'lax'));
    }

    public function clear(): void
    {
        Cookie::queue(Cookie::make(self::COOKIE_NAME, '', -1, '/', null, true, true, false, 'lax'));
    }
}
