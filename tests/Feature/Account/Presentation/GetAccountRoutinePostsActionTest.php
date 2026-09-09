<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAccountRoutinePostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_requested_accounts_routine_posts_and_logged_in_accounts_posts(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $accountIdentifier, 'account_name' => '作成者', 'email_address' => 'author@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => '22222222-2222-4222-8222-222222222222', 'account_identifier' => $accountIdentifier, 'routine_name' => '朝活', 'routine_execution_minutes' => 10, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_identifier' => '22222222-2222-4222-8222-222222222222', 'post_category' => 'routine', 'post_like_count' => 1, 'post_support_count' => 2, 'available' => true, 'created_at' => '2026-09-09 10:00:00', 'updated_at' => now()]);

        $this->getJson("/api/accounts/{$accountIdentifier}/posts")
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333')->assertJsonPath('total', 1);
        $this->withSession(['account_identifier' => $accountIdentifier])->getJson('/api/my/posts')
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333');
    }

    public function test_returns_unauthorized_when_not_logged_in(): void
    {
        $this->getJson('/api/my/posts')->assertUnauthorized();
    }
}
