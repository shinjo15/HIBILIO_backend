<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Infrastructure\Query\GetMySupports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInput;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInterface;
use Src\Support\Infrastructure\Query\GetMySupports\GetMySupports;
use Tests\TestCase;

final class GetMySupportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_my_action_supports_in_descending_support_datetime_order(): void
    {
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createRoutine('22222222-2222-4222-8222-222222222222');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'action', 4, 8);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', 'action', 2, 3);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', 'routine', 1, 9);
        $this->createPost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '22222222-2222-4222-8222-222222222222', 'action', 7, 6);
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 12:00:00');
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 13:00:00');
        $this->createSupport('44444444-4444-4444-8444-444444444444', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-09-03 14:00:00');

        $input = new GetMySupportsInput('33333333-3333-4333-8333-333333333333', 1, 10);

        self::assertSame(10, $input->numberOfItemsPerPage());

        $result = (new GetMySupports)->execute($input);

        self::assertSame(2, $result->total());
        self::assertSame([
            [
                'postIdentifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
                'routineExecutionIdentifier' => null,
                'postCategory' => 'action',
                'postLikeCount' => 2,
                'postSupportCount' => 3,
                'liked' => false,
                'supported' => true,
                'supportedAt' => '2026-09-03T12:00:00+00:00',
            ],
            [
                'postIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
                'routineExecutionIdentifier' => null,
                'postCategory' => 'action',
                'postLikeCount' => 4,
                'postSupportCount' => 8,
                'liked' => false,
                'supported' => true,
                'supportedAt' => '2026-09-03T10:00:00+00:00',
            ],
        ], $result->items());
    }

    public function test_it_paginates_my_action_supports(): void
    {
        $this->createRoutine('11111111-1111-4111-8111-111111111111');
        $this->createPost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'action', 0, 1);
        $this->createPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '11111111-1111-4111-8111-111111111111', 'action', 0, 2);
        $this->createPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '11111111-1111-4111-8111-111111111111', 'action', 0, 3);
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-03 10:00:00');
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-03 11:00:00');
        $this->createSupport('33333333-3333-4333-8333-333333333333', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-09-03 12:00:00');

        $input = new GetMySupportsInput('33333333-3333-4333-8333-333333333333', 2, 2);

        self::assertSame(2, $input->numberOfItemsPerPage());

        $result = (new GetMySupports)->execute($input);

        self::assertSame(3, $result->total());
        self::assertSame([[
            'postIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routineIdentifier' => '11111111-1111-4111-8111-111111111111',
            'routineExecutionIdentifier' => null,
            'postCategory' => 'action',
            'postLikeCount' => 0,
            'postSupportCount' => 1,
            'liked' => false,
            'supported' => true,
            'supportedAt' => '2026-09-03T10:00:00+00:00',
        ]], $result->items());
    }

    public function test_query_interface_is_bound_to_infrastructure_query(): void
    {
        self::assertInstanceOf(GetMySupports::class, $this->app->make(GetMySupportsInterface::class));
    }

    private function createRoutine(string $routineIdentifier): void
    {
        DB::table('routines')->insert(['routine_identifier' => $routineIdentifier, 'routine_name' => '朝活', 'account_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_execution_minutes' => 1, 'available' => true, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createPost(string $postIdentifier, string $routineIdentifier, string $postCategory, int $postLikeCount, int $postSupportCount): void
    {
        DB::table('posts')->insert(['post_identifier' => $postIdentifier, 'routine_identifier' => $routineIdentifier, 'post_category' => $postCategory, 'post_like_count' => $postLikeCount, 'post_support_count' => $postSupportCount, 'available' => true, 'created_at' => '2026-09-03 09:00:00', 'updated_at' => '2026-09-03 09:00:00']);
    }

    private function createSupport(string $accountIdentifier, string $postIdentifier, string $createdAt): void
    {
        DB::table('supports')->insert(['account_identifier' => $accountIdentifier, 'post_identifier' => $postIdentifier, 'created_at' => $createdAt, 'updated_at' => $createdAt]);
    }
}
