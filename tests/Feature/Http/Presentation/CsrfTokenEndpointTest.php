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
}
