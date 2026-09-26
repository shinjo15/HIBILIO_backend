<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Infrastructure;

use Illuminate\Support\Facades\Cookie;
use Src\Authentication\Infrastructure\Service\LaravelPersistentLoginCookieService;
use Tests\TestCase;

final class LaravelPersistentLoginCookieServiceTest extends TestCase
{
    public function test_queues_an_http_only_secure_lax_same_site_cookie(): void
    {
        Cookie::flushQueuedCookies();

        (new LaravelPersistentLoginCookieService)->queue('selector.validator', 43200);

        $cookies = Cookie::getQueuedCookies();

        self::assertCount(1, $cookies);
        self::assertSame('hibilio_persistent_login', $cookies[0]->getName());
        self::assertSame('selector.validator', $cookies[0]->getValue());
        self::assertTrue($cookies[0]->isSecure());
        self::assertTrue($cookies[0]->isHttpOnly());
        self::assertSame('lax', $cookies[0]->getSameSite());
    }
}
