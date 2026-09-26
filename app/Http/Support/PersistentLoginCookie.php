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
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site'),
        );
    }
}
