<?php

declare(strict_types=1);

namespace Tests\Unit\Like\Infrastructure\Query\GetMyLikes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInput;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInterface;
use Src\Like\Infrastructure\Query\GetMyLikes\GetMyLikes;
use Tests\TestCase;

final class GetMyLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_my_available_routine_likes_in_descending_like_datetime_and_post_identifier_order(): void
    {
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createRoutine('22222222-2222-4222-8222-222222222222');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'routine', true, 4, 8);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', 'routine', true, 2, 3);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', 'routine', true, 1, 9);
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '11111111-1111-4111-8111-111111111111', 'action', true, 7, 6);
        $this->createPost('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '22222222-2222-4222-8222-222222222222', 'routine', false, 5, 1);
        $this->createLike('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 12:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 12:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-09-03 13:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '2026-09-03 14:00:00');

        $result = (new GetMyLikes)->execute(new GetMyLikesInput('33333333-3333-4333-8333-333333333333', 1, 10));

        self::assertSame(3, $result->total());
        self::assertSame([
            [
                'postIdentifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
                'postCategory' => 'routine',
                'postLikeCount' => 2,
                'postSupportCount' => 3,
                'likedAt' => '2026-09-03T12:00:00+00:00',
            ],
            [
                'postIdentifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
                'postCategory' => 'routine',
                'postLikeCount' => 1,
                'postSupportCount' => 9,
                'likedAt' => '2026-09-03T12:00:00+00:00',
            ],
            [
                'postIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
                'postCategory' => 'routine',
                'postLikeCount' => 4,
                'postSupportCount' => 8,
                'likedAt' => '2026-09-03T10:00:00+00:00',
            ],
        ], $result->items());
    }

    public function test_it_paginates_my_available_routine_likes_and_returns_the_total(): void
    {
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'routine', true, 0, 1);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', 'routine', true, 0, 2);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', 'routine', true, 0, 3);
        $this->createLike('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 11:00:00');
        $this->createLike('33333333-3333-4333-8333-333333333333', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 12:00:00');

        $result = (new GetMyLikes)->execute(new GetMyLikesInput('33333333-3333-4333-8333-333333333333', 2, 2));

        self::assertSame(3, $result->total());
        self::assertSame([[
            'postIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
            'postCategory' => 'routine',
            'postLikeCount' => 0,
            'postSupportCount' => 1,
            'likedAt' => '2026-09-03T10:00:00+00:00',
        ]], $result->items());
    }

    public function test_query_interface_is_bound_to_infrastructure_query(): void
    {
        self::assertInstanceOf(GetMyLikes::class, $this->app->make(GetMyLikesInterface::class));
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
