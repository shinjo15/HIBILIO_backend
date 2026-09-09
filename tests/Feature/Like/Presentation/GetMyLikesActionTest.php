<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Tests\TestCase;

final class GetMyLikesActionTest extends TestCase
{
    public function test_returns_validation_errors_for_invalid_pagination_parameters(): void
    {
        $this->withSession(['account_identifier' => '33333333-3333-4333-8333-333333333333'])
            ->getJson('/api/my/likes?page=0&number_of_items_per_page=zero')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    public function test_returns_unauthorized_when_the_session_has_no_account_identifier(): void
    {
        $this->getJson('/api/my/likes?page=1&number_of_items_per_page=1')->assertUnauthorized();
    }
}
