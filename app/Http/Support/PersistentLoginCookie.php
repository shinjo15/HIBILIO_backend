<?php

declare(strict_types=1);

namespace App\Http\Support;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

final class PersistentLoginCookie
{
    public static function make(string $selector, string $rawValidator, DateTimeImmutable $expiresAt): Cookie
    {
        return Cookie::create(
            'persistent_login',
            $selector.':'.$rawValidator,
            $expiresAt,
            '/',
            config('session.domain'),
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    public static function forget(): Cookie
    {
        return Cookie::create(
            'persistent_login',
            null,
            new DateTimeImmutable('-1 year'),
            '/',
            config('session.domain'),
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
