<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Cookie;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;

final readonly class LaravelPersistentLoginCookieService implements PersistentLoginCookieServiceInterface
{
    public const COOKIE_NAME = 'hibilio_persistent_login';

    public function queue(string $value, int $minutes): void
    {
        Cookie::queue(Cookie::make(self::COOKIE_NAME, $value, $minutes, '/', null, true, true, false, 'lax'));
    }
}
