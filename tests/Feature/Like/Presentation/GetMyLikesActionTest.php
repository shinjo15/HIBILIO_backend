<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetMyLikesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_the_session_accounts_available_routine_likes_with_requested_number_of_items_per_page_and_total(): void
    {
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'routine', true, 3, 8);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', 'routine', true, 2, 4);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', 'routine', false, 1, 6);
        $this->createLike('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 12:00:00');
        $this->createLike('44444444-4444-4444-8444-444444444444', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 13:00:00');

        $response = $this->withSession(['account_identifier' => '33333333-3333-4333-8333-333333333333'])
            ->getJson('/api/my/likes?page=1&number_of_items_per_page=1&account_identifier=44444444-4444-4444-8444-444444444444');

        $response->assertOk()
            ->assertJsonCount(1, 'likes')
            ->assertJson([
                'likes' => [[
                    'post_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                    'routine_identifier' => '11111111-1111-4111-8111-111111111111',
                    'post_category' => 'routine',
                    'post_like_count' => 2,
                    'post_support_count' => 4,
                    'liked_at' => '2026-09-03T12:00:00+00:00',
                ]],
                'total' => 2,
            ]);
    }

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

    private function createRoutine(string $routineIdentifier): void
    {
        DB::table('routines')->insert(['routine_identifier' => $routineIdentifier, 'routine_name' => '朝活', 'account_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_execution_minutes' => 1, 'available' => true, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createPost(string $postIdentifier, string $routineIdentifier, string $postCategory, bool $available, int $postLikeCount, int $postSupportCount): void
    {
        DB::table('posts')->insert(['post_identifier' => $postIdentifier, 'routine_identifier' => $routineIdentifier, 'post_category' => $postCategory, 'post_like_count' => $postLikeCount, 'post_support_count' => $postSupportCount, 'available' => $available, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createLike(string $accountIdentifier, string $postIdentifier, string $createdAt): void
    {
        DB::table('likes')->insert(['account_identifier' => $accountIdentifier, 'post_identifier' => $postIdentifier, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }
}
