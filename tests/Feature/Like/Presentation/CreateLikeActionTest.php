<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateLikeActionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_rejects_like_when_the_session_account_is_temporarily_banned(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier,
            'account_name' => '停止中Account',
            'email_address' => "{$accountIdentifier}@example.com",
            'available' => true,
            'status' => 'temporarily_banned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $accountIdentifier])
            ->postJson('/api/likes', ['post_identifier' => '22222222-2222-4222-8222-222222222222'])
            ->assertUnauthorized();
    }
}
