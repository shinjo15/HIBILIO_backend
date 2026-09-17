<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Infrastructure\Service;

use Illuminate\Http\Request;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Service\LaravelAuthService;
use Tests\TestCase;

final class LaravelAuthServiceTest extends TestCase
{
    public function test_account_identifier_returns_null_when_session_has_no_account_identifier(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);

        self::assertNull((new LaravelAuthService($request))->accountIdentifier());
    }

    public function test_login_regenerates_session_and_stores_account_identifier(): void
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        $authService = new LaravelAuthService($request);

        $authService->login(new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'));

        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $authService->accountIdentifier());
    }
}
