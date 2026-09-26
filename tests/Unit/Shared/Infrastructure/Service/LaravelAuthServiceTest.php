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
        $request = $this->request();

        self::assertNull((new LaravelAuthService($request))->accountIdentifier());
    }

    public function test_login_regenerates_session_and_stores_account_identifier(): void
    {
        $request = $this->request();
        $service = new LaravelAuthService($request);

        $service->login(new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'));

        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $service->accountIdentifier());
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $request = $this->request();
        $request->session()->put(LaravelAuthService::SESSION_KEY, 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028');
        $csrfToken = $request->session()->token();

        (new LaravelAuthService($request))->logout();

        self::assertFalse($request->session()->has(LaravelAuthService::SESSION_KEY));
        self::assertNotSame($csrfToken, $request->session()->token());
    }

    private function request(): Request
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);

        return $request;
    }
}
