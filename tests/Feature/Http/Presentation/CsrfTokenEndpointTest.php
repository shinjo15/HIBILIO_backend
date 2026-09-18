<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Presentation;

use Tests\TestCase;

final class CsrfTokenEndpointTest extends TestCase
{
    public function test_returns_ok_for_an_anonymous_request(): void
    {
        $response = $this->getJson('/api/csrf-token');

        $response
            ->assertOk()
            ->assertJsonStructure(['csrf_token'])
            ->assertJsonCount(1)
            ->assertCookie(config('session.cookie'));

        $csrfToken = $response->json('csrf_token');

        self::assertIsString($csrfToken);
        self::assertSame($this->app['session.store']->token(), $csrfToken);
    }

    public function test_allows_credentialed_csrf_token_requests_from_an_allowed_origin(): void
    {
        $this->getJson('/api/csrf-token', ['Origin' => 'http://localhost:5173'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_allows_csrf_header_on_preflight_from_an_allowed_origin(): void
    {
        $this->call('OPTIONS', '/api/my/account', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PATCH',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type, x-csrf-token',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->assertHeader('Access-Control-Allow-Headers', 'Content-Type, X-CSRF-TOKEN');
    }

    public function test_does_not_allow_an_unapproved_origin(): void
    {
        $this->getJson('/api/csrf-token', ['Origin' => 'https://untrusted.example'])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }
}
