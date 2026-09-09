<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Tests\TestCase;

final class CreateLikeActionTest extends TestCase
{
    public function test_requires_an_authenticated_account(): void
    {
        $this->postJson('/api/likes', ['post_identifier' => '11111111-1111-4111-8111-111111111111'])->assertUnauthorized();
    }

    public function test_validates_post_identifier(): void
    {
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->postJson('/api/likes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['post_identifier']);
    }
}
