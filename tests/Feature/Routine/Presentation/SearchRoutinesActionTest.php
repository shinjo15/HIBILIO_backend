<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class SearchRoutinesActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_matching_routines_and_routine_execution_posts_in_descending_published_order(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = '33333333-3333-4333-8333-333333333333';
        $executionIdentifier = '44444444-4444-4444-8444-444444444444';
        $tagIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->tag($tagIdentifier, '朝活');
        $this->routine($routineIdentifier, $authorIdentifier, '朝のストレッチ', '2026-09-01 09:00:00');
        $this->routineTag($routineIdentifier, $tagIdentifier);
        DB::table('routines')->where('routine_identifier', $routineIdentifier)->update(['routine_execution_minutes' => 15]);
        DB::table('routine_actions')->insert([
            'routine_action_identifier' => '66666666-6666-4666-8666-666666666666',
            'routine_identifier' => $routineIdentifier,
            'action_name' => '屈伸',
            'action_minutes' => 5,
            'available' => true,
            'created_at' => '2026-09-01 09:01:00',
            'updated_at' => '2026-09-01 09:01:00',
        ]);
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-03 10:00:00');
        DB::table('routine_execution_actions')->insert([
            'routine_execution_identifier' => $executionIdentifier,
            'routine_action_identifier' => '66666666-6666-4666-8666-666666666666',
            'created_at' => '2026-09-03 10:00:00',
            'updated_at' => '2026-09-03 10:00:00',
        ]);
        DB::table('routine_executions')->where('routine_execution_identifier', $executionIdentifier)->update(['routine_execution_memo' => '完了']);
        $this->routinePost('77777777-7777-4777-8777-777777777777', $routineIdentifier, 4, '2026-09-01 10:00:00');
        $this->executionPost('55555555-5555-4555-8555-555555555555', $routineIdentifier, $executionIdentifier, '2026-09-03 11:00:00');
        DB::table('posts')->where('post_identifier', '55555555-5555-4555-8555-555555555555')->update(['post_like_count' => 6, 'post_support_count' => 3]);
        DB::table('likes')->insert(['account_identifier' => $authorIdentifier, 'post_identifier' => '77777777-7777-4777-8777-777777777777', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('supports')->insert(['account_identifier' => $authorIdentifier, 'post_identifier' => '55555555-5555-4555-8555-555555555555', 'created_at' => now(), 'updated_at' => now()]);

        $this->withSession(['account_identifier' => $authorIdentifier])
            ->getJson('/api/routines/search?title=ストレ&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertExactJson([
                'items' => [
                    [
                        'item_type' => 'routine_execution',
                        'routine_identifier' => $routineIdentifier,
                        'routine_execution_identifier' => $executionIdentifier,
                        'routine_name' => '朝のストレッチ',
                        'account_identifier' => $executorIdentifier,
                        'account_name' => '実行者',
                        'icon_image_url' => "https://images.example/accounts/{$executorIdentifier}/icon",
                        'tags' => [[
                            'tag_identifier' => $tagIdentifier,
                            'tag_name' => '朝活',
                        ]],
                        'published_at' => '2026-09-03T11:00:00+00:00',
                        'posted_at' => '2026-09-03T11:00:00+00:00',
                        'post_identifier' => '55555555-5555-4555-8555-555555555555',
                        'post_like_count' => 6,
                        'routine_execution_minutes' => 15,
                        'routine_actions' => [[
                            'routine_action_identifier' => '66666666-6666-4666-8666-666666666666',
                            'action_name' => '屈伸',
                            'action_minutes' => 5,
                        ]],
                        'execution_count' => 1,
                        'customization_count' => 0,
                        'support_count' => 3,
                        'supported' => true,
                        'executed_action_count' => 1,
                        'routine_execution_memo' => '完了',
                    ],
                    [
                        'item_type' => 'routine',
                        'routine_identifier' => $routineIdentifier,
                        'routine_execution_identifier' => null,
                        'routine_name' => '朝のストレッチ',
                        'account_identifier' => $authorIdentifier,
                        'account_name' => '作成者',
                        'icon_image_url' => "https://images.example/accounts/{$authorIdentifier}/icon",
                        'tags' => [[
                            'tag_identifier' => $tagIdentifier,
                            'tag_name' => '朝活',
                        ]],
                        'published_at' => '2026-09-01T09:00:00+00:00',
                        'posted_at' => '2026-09-01T10:00:00+00:00',
                        'post_identifier' => '77777777-7777-4777-8777-777777777777',
                        'routine_execution_minutes' => 15,
                        'routine_actions' => [[
                            'routine_action_identifier' => '66666666-6666-4666-8666-666666666666',
                            'action_name' => '屈伸',
                            'action_minutes' => 5,
                        ]],
                        'post_like_count' => 4,
                        'liked' => true,
                        'execution_count' => 1,
                        'customization_count' => 0,
                    ],
                ],
                'total' => 2,
            ]);
    }

    public function test_returns_null_post_specific_fields_for_a_routine_without_a_post(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';

        $this->account($authorIdentifier, '作成者');
        $this->routine($routineIdentifier, $authorIdentifier, '投稿なしルーティン', '2026-09-01 09:00:00');

        $this->getJson('/api/routines/search?title=投稿なし')
            ->assertOk()
            ->assertJsonStructure([
                'items' => [[
                    'post_identifier',
                    'posted_at',
                    'post_like_count',
                    'liked',
                ]],
            ])
            ->assertJsonPath('items.0.post_identifier', null)
            ->assertJsonPath('items.0.posted_at', null)
            ->assertJsonPath('items.0.post_like_count', null)
            ->assertJsonPath('items.0.liked', null);
    }

    public function test_selects_the_earliest_available_routine_post_without_duplicating_the_search_item(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineIdentifier = '22222222-2222-4222-8222-222222222222';

        $this->account($authorIdentifier, '作成者');
        $this->routine($routineIdentifier, $authorIdentifier, '重複投稿ルーティン', '2026-09-01 09:00:00');
        $this->routinePost('99999999-9999-4999-8999-999999999999', $routineIdentifier, 9, '2026-09-02 09:00:00');
        $this->routinePost('88888888-8888-4888-888888888888', $routineIdentifier, 8, '2026-09-01 09:00:00');
        $this->routinePost('77777777-7777-4777-8777-777777777777', $routineIdentifier, 7, '2026-09-01 09:00:00');

        $this->getJson('/api/routines/search?title=重複投稿')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.post_identifier', '77777777-7777-4777-8777-777777777777')
            ->assertJsonPath('items.0.post_like_count', 7);
    }

    public function test_returns_anonymous_reaction_states_for_existing_posts(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = '33333333-3333-4333-8333-333333333333';
        $executionIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->routine($routineIdentifier, $authorIdentifier, '匿名リアクション', '2026-09-01 09:00:00');
        $this->routinePost('55555555-5555-4555-8555-555555555555', $routineIdentifier, 2, '2026-09-01 09:00:00');
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-02 09:00:00');
        $this->executionPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $executionIdentifier, '2026-09-02 09:00:00');
        DB::table('posts')->where('post_identifier', '66666666-6666-4666-8666-666666666666')->update(['post_support_count' => 3]);

        $response = $this->getJson('/api/routines/search?title=匿名リアクション')
            ->assertOk();

        self::assertFalse($response->json('items.0.supported'));
        self::assertFalse($response->json('items.1.liked'));
    }

    public function test_uses_a_bounded_number_of_database_queries_per_page(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = '33333333-3333-4333-8333-333333333333';
        $executionIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->routine($routineIdentifier, $authorIdentifier, '問い合わせ上限', '2026-09-01 09:00:00');
        $this->routinePost('55555555-5555-4555-8555-555555555555', $routineIdentifier, 0, '2026-09-01 09:00:00');
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-01 10:00:00');
        $this->executionPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $executionIdentifier, '2026-09-01 10:00:00');
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->getJson('/api/routines/search?title=問い合わせ上限')->assertOk();
        $singleItemQueryCount = count(DB::getQueryLog());

        $secondRoutineIdentifier = '77777777-7777-4777-8777-777777777777';
        $secondExecutionIdentifier = '88888888-8888-4888-888888888888';
        $customizedRoutineIdentifier = '99999999-9999-4999-8999-999999999999';
        $this->routine($secondRoutineIdentifier, $authorIdentifier, '問い合わせ上限二', '2026-09-02 09:00:00');
        $this->routinePost('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $secondRoutineIdentifier, 0, '2026-09-02 09:00:00');
        $this->routineExecution($secondExecutionIdentifier, $secondRoutineIdentifier, $executorIdentifier, '2026-09-02 10:00:00');
        $this->executionPost('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $secondRoutineIdentifier, $secondExecutionIdentifier, '2026-09-02 10:00:00');
        $this->executionPost('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $secondRoutineIdentifier, $secondExecutionIdentifier, '2026-09-02 11:00:00');
        DB::table('posts')->where('post_identifier', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc')->update(['available' => false]);
        $this->routine($customizedRoutineIdentifier, $authorIdentifier, '問い合わせ上限カスタム', '2026-09-02 12:00:00');
        DB::table('routines')->where('routine_identifier', $customizedRoutineIdentifier)->update(['parent_routine_identifier' => $routineIdentifier]);
        $this->routinePost('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $customizedRoutineIdentifier, 0, '2026-09-02 12:00:00');
        DB::table('posts')->where('post_identifier', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd')->update(['available' => false]);
        DB::flushQueryLog();

        $this->getJson('/api/routines/search?title=問い合わせ上限')->assertOk();
        $multipleItemQueryCount = count(DB::getQueryLog());

        self::assertSame($singleItemQueryCount, $multipleItemQueryCount);
        self::assertLessThanOrEqual(6, $multipleItemQueryCount);
    }

    public function test_returns_only_items_that_have_all_specified_tags(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $firstTagIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $secondTagIdentifier = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $matchingRoutineIdentifier = '22222222-2222-4222-8222-222222222222';
        $otherRoutineIdentifier = '33333333-3333-4333-8333-333333333333';

        $this->account($authorIdentifier, '作成者');
        $this->tag($firstTagIdentifier, '朝活');
        $this->tag($secondTagIdentifier, '運動');
        $this->routine($matchingRoutineIdentifier, $authorIdentifier, '両方のタグ', '2026-09-01 09:00:00');
        $this->routine($otherRoutineIdentifier, $authorIdentifier, '片方のタグ', '2026-09-02 09:00:00');
        $this->routineTag($matchingRoutineIdentifier, $firstTagIdentifier);
        $this->routineTag($matchingRoutineIdentifier, $secondTagIdentifier);
        $this->routineTag($otherRoutineIdentifier, $firstTagIdentifier);

        $this->getJson("/api/routines/search?tag_identifiers[]={$firstTagIdentifier}&tag_identifiers[]={$secondTagIdentifier}")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $matchingRoutineIdentifier);
    }

    public function test_requires_both_title_and_tag_conditions_when_both_are_specified(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $tagIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $matchingRoutineIdentifier = '22222222-2222-4222-8222-222222222222';
        $titleOnlyRoutineIdentifier = '33333333-3333-4333-8333-333333333333';
        $tagOnlyRoutineIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->account($authorIdentifier, '作成者');
        $this->tag($tagIdentifier, '朝活');
        $this->routine($matchingRoutineIdentifier, $authorIdentifier, '朝の読書', '2026-09-01 09:00:00');
        $this->routine($titleOnlyRoutineIdentifier, $authorIdentifier, '朝の散歩', '2026-09-02 09:00:00');
        $this->routine($tagOnlyRoutineIdentifier, $authorIdentifier, '夜の読書', '2026-09-03 09:00:00');
        $this->routineTag($matchingRoutineIdentifier, $tagIdentifier);
        $this->routineTag($tagOnlyRoutineIdentifier, $tagIdentifier);

        $this->getJson("/api/routines/search?title=朝&tag_identifiers[]={$tagIdentifier}")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $matchingRoutineIdentifier);
    }

    public function test_requires_a_search_condition_and_valid_tag_identifiers(): void
    {
        $this->getJson('/api/routines/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'tag_identifiers']);

        $this->getJson('/api/routines/search?tag_identifiers[]=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_identifiers.0']);
    }

    public function test_excludes_unavailable_inactive_and_blocked_sources_for_an_authenticated_searcher(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $visibleAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $blockedAuthorIdentifier = '33333333-3333-4333-8333-333333333333';
        $inactiveAuthorIdentifier = '44444444-4444-4444-8444-444444444444';
        $unavailableAuthorIdentifier = '55555555-5555-4555-8555-555555555555';

        $this->account($viewerIdentifier, '検索者');
        $this->account($visibleAuthorIdentifier, '表示する');
        $this->account($blockedAuthorIdentifier, 'ブロック対象');
        $this->account($inactiveAuthorIdentifier, '停止中', 'temporarily_banned');
        $this->account($unavailableAuthorIdentifier, '利用停止', 'active', false);
        $this->routine('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $visibleAuthorIdentifier, '対象表示', '2026-09-01 09:00:00');
        $this->routine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $blockedAuthorIdentifier, '対象ブロック', '2026-09-02 09:00:00');
        $this->routine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $inactiveAuthorIdentifier, '対象停止', '2026-09-03 09:00:00');
        $this->routine('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $unavailableAuthorIdentifier, '対象利用停止', '2026-09-04 09:00:00');
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $viewerIdentifier,
            'blocked_account_identifier' => $blockedAuthorIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/routines/search?title=対象')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.account_identifier', $visibleAuthorIdentifier);
    }

    public function test_excludes_private_accounts_routines_and_routine_execution_posts_from_search(): void
    {
        $publicAuthorIdentifier = '11111111-1111-4111-8111-111111111111';
        $privateAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $publicExecutorIdentifier = '33333333-3333-4333-8333-333333333333';
        $privateExecutorIdentifier = '44444444-4444-4444-8444-444444444444';
        $publicRoutineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $privateRoutineIdentifier = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $publicExecutionIdentifier = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        $privateExecutionIdentifier = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

        $this->account($publicAuthorIdentifier, '公開作成者');
        $this->account($privateAuthorIdentifier, '鍵作成者', 'active', true, 'private');
        $this->account($publicExecutorIdentifier, '公開実行者');
        $this->account($privateExecutorIdentifier, '鍵実行者', 'active', true, 'private');
        $this->routine($publicRoutineIdentifier, $publicAuthorIdentifier, '対象公開Routine', '2026-09-01 09:00:00');
        $this->routine($privateRoutineIdentifier, $privateAuthorIdentifier, '対象鍵Routine', '2026-09-02 09:00:00');
        $this->routineExecution($publicExecutionIdentifier, $publicRoutineIdentifier, $publicExecutorIdentifier, '2026-09-03 09:00:00');
        $this->routineExecution($privateExecutionIdentifier, $publicRoutineIdentifier, $privateExecutorIdentifier, '2026-09-04 09:00:00');
        $this->executionPost('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $publicRoutineIdentifier, $publicExecutionIdentifier, '2026-09-03 10:00:00');
        $this->executionPost('ffffffff-ffff-4fff-8fff-ffffffffffff', $publicRoutineIdentifier, $privateExecutionIdentifier, '2026-09-04 10:00:00');

        $this->getJson('/api/routines/search?title=対象&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.account_identifier', $publicExecutorIdentifier)
            ->assertJsonPath('items.1.account_identifier', $publicAuthorIdentifier);
    }

    public function test_excludes_private_unavailable_and_inactive_routine_author_execution_posts_before_total_and_pagination(): void
    {
        $publicAuthorIdentifier = '11111111-1111-4111-8111-111111111111';
        $privateAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $inactiveAuthorIdentifier = '33333333-3333-4333-8333-333333333333';
        $unavailableAuthorIdentifier = '44444444-4444-4444-8444-444444444444';
        $executorIdentifier = '55555555-5555-4555-8555-555555555555';
        $publicRoutineIdentifier = '66666666-6666-4666-8666-666666666666';
        $privateRoutineIdentifier = '77777777-7777-4777-8777-777777777777';
        $inactiveRoutineIdentifier = '88888888-8888-4888-888888888888';
        $unavailableRoutineIdentifier = '99999999-9999-4999-8999-999999999999';

        $this->account($publicAuthorIdentifier, '公開作成者');
        $this->account($privateAuthorIdentifier, '鍵作成者', 'active', true, 'private');
        $this->account($inactiveAuthorIdentifier, '停止作成者', 'temporarily_banned');
        $this->account($unavailableAuthorIdentifier, '利用停止作成者', 'active', false);
        $this->account($executorIdentifier, '公開実行者');
        foreach ([
            [$publicRoutineIdentifier, $publicAuthorIdentifier, 'aaaaaaa1-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbb1-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-04 10:00:00'],
            [$privateRoutineIdentifier, $privateAuthorIdentifier, 'aaaaaaa2-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbb2-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-05 10:00:00'],
            [$inactiveRoutineIdentifier, $inactiveAuthorIdentifier, 'aaaaaaa3-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbb3-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-06 10:00:00'],
            [$unavailableRoutineIdentifier, $unavailableAuthorIdentifier, 'aaaaaaa4-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'bbbbbbb4-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-09-07 10:00:00'],
        ] as [$routineIdentifier, $authorIdentifier, $executionIdentifier, $postIdentifier, $createdAt]) {
            $this->routine($routineIdentifier, $authorIdentifier, '作者フィルターRoutine', '2026-09-01 09:00:00');
            $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, $createdAt);
            $this->executionPost($postIdentifier, $routineIdentifier, $executionIdentifier, $createdAt);
        }
        DB::table('posts')->where('post_identifier', 'bbbbbbb1-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->update(['post_like_count' => 5, 'post_support_count' => 2]);

        $this->getJson('/api/routines/search?title=作者フィルター&page=1&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.item_type', 'routine_execution')
            ->assertJsonPath('items.0.routine_identifier', $publicRoutineIdentifier)
            ->assertJsonPath('items.0.post_identifier', 'bbbbbbb1-bbbb-4bbb-8bbb-bbbbbbbbbbbb')
            ->assertJsonPath('items.0.post_like_count', 5)
            ->assertJsonPath('items.0.support_count', 2);

        $this->getJson('/api/routines/search?title=作者フィルター&page=2&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.item_type', 'routine')
            ->assertJsonPath('items.0.routine_identifier', $publicRoutineIdentifier);

        $this->withSession(['account_identifier' => $privateAuthorIdentifier])
            ->getJson('/api/routines/search?title=作者フィルター&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.routine_identifier', $publicRoutineIdentifier);
    }

    public function test_excludes_execution_posts_for_both_block_directions_between_viewer_and_public_routine_author(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $executorIdentifier = '33333333-3333-4333-8333-333333333333';
        $routineIdentifier = '44444444-4444-4444-8444-444444444444';
        $executionIdentifier = '55555555-5555-4555-8555-555555555555';

        $this->account($viewerIdentifier, '検索者');
        $this->account($authorIdentifier, '公開作成者');
        $this->account($executorIdentifier, '公開実行者');
        $this->routine($routineIdentifier, $authorIdentifier, '作者ブロックRoutine', '2026-09-01 09:00:00');
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-02 09:00:00');
        $this->executionPost('66666666-6666-4666-8666-666666666666', $routineIdentifier, $executionIdentifier, '2026-09-02 10:00:00');

        foreach ([
            ['blocking_account_identifier' => $authorIdentifier, 'blocked_account_identifier' => $viewerIdentifier],
            ['blocking_account_identifier' => $viewerIdentifier, 'blocked_account_identifier' => $authorIdentifier],
        ] as $block) {
            DB::table('blocks')->insert($block + ['created_at' => now(), 'updated_at' => now()]);
            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson('/api/routines/search?title=作者ブロックRoutine')
                ->assertOk()
                ->assertJsonPath('total', 0);
            DB::table('blocks')->delete();
        }
    }

    public function test_excludes_private_unavailable_and_inactive_executors_action_posts_and_both_executor_block_directions(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $publicExecutorIdentifier = '33333333-3333-4333-8333-333333333333';
        $privateExecutorIdentifier = '44444444-4444-4444-8444-444444444444';
        $inactiveExecutorIdentifier = '55555555-5555-4555-8555-555555555555';
        $unavailableExecutorIdentifier = '66666666-6666-4666-8666-666666666666';
        $routineIdentifier = '77777777-7777-4777-8777-777777777777';

        $this->account($viewerIdentifier, '検索者');
        $this->account($authorIdentifier, '公開作成者');
        $this->account($publicExecutorIdentifier, '公開実行者');
        $this->account($privateExecutorIdentifier, '鍵実行者', 'active', true, 'private');
        $this->account($inactiveExecutorIdentifier, '停止実行者', 'temporarily_banned');
        $this->account($unavailableExecutorIdentifier, '利用停止実行者', 'active', false);
        $this->routine($routineIdentifier, $authorIdentifier, '実行者フィルターRoutine', '2026-09-01 09:00:00');
        foreach ([
            [$publicExecutorIdentifier, '88888888-8888-4888-888888888888', 'aaaaaaa1-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-04 10:00:00'],
            [$privateExecutorIdentifier, '99999999-9999-4999-8999-999999999999', 'aaaaaaa2-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-05 10:00:00'],
            [$inactiveExecutorIdentifier, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'aaaaaaa3-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-06 10:00:00'],
            [$unavailableExecutorIdentifier, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'aaaaaaa4-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-09-07 10:00:00'],
        ] as [$executorIdentifier, $executionIdentifier, $postIdentifier, $createdAt]) {
            $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, $createdAt);
            $this->executionPost($postIdentifier, $routineIdentifier, $executionIdentifier, $createdAt);
        }
        DB::table('posts')->where('post_identifier', 'aaaaaaa1-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->update(['post_like_count' => 7, 'post_support_count' => 4]);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson('/api/routines/search?title=実行者フィルター&page=1&number_of_items_per_page=20')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.item_type', 'routine_execution')
            ->assertJsonPath('items.0.account_identifier', $publicExecutorIdentifier)
            ->assertJsonPath('items.0.post_identifier', 'aaaaaaa1-aaaa-4aaa-8aaa-aaaaaaaaaaaa')
            ->assertJsonPath('items.0.post_like_count', 7)
            ->assertJsonPath('items.0.support_count', 4)
            ->assertJsonPath('items.1.item_type', 'routine');

        foreach ([
            ['blocking_account_identifier' => $publicExecutorIdentifier, 'blocked_account_identifier' => $viewerIdentifier],
            ['blocking_account_identifier' => $viewerIdentifier, 'blocked_account_identifier' => $publicExecutorIdentifier],
        ] as $block) {
            DB::table('blocks')->insert($block + ['created_at' => now(), 'updated_at' => now()]);
            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson('/api/routines/search?title=実行者フィルター&page=1&number_of_items_per_page=20')
                ->assertOk()
                ->assertJsonPath('total', 1)
                ->assertJsonPath('items.0.item_type', 'routine');
            DB::table('blocks')->delete();
        }
    }

    public function test_paginates_the_combined_items(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $executorIdentifier = '22222222-2222-4222-8222-222222222222';
        $firstRoutineIdentifier = '22222222-2222-4222-8222-222222222222';
        $secondRoutineIdentifier = '33333333-3333-4333-8333-333333333333';
        $executionIdentifier = '44444444-4444-4444-8444-444444444444';

        $this->account($authorIdentifier, '作成者');
        $this->account($executorIdentifier, '実行者');
        $this->routine($firstRoutineIdentifier, $authorIdentifier, 'ページ検索一', '2026-09-03 09:00:00');
        $this->routine($secondRoutineIdentifier, $authorIdentifier, 'ページ検索二', '2026-09-02 09:00:00');
        $this->routinePost('55555555-5555-4555-8555-555555555555', $firstRoutineIdentifier, 0, '2026-09-03 08:00:00');
        $this->routineExecution($executionIdentifier, $firstRoutineIdentifier, $executorIdentifier, '2026-09-03 10:00:00');
        $this->executionPost('66666666-6666-4666-8666-666666666666', $firstRoutineIdentifier, $executionIdentifier, '2026-09-03 10:00:00');

        $this->getJson('/api/routines/search?title=ページ検索&page=1&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('items.0.item_type', 'routine_execution')
            ->assertJsonPath('items.0.routine_identifier', $firstRoutineIdentifier)
            ->assertJsonPath('items.0.published_at', '2026-09-03T10:00:00+00:00')
            ->assertJsonPath('items.0.posted_at', '2026-09-03T10:00:00+00:00');

        $this->getJson('/api/routines/search?title=ページ検索&page=2&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('items.0.item_type', 'routine')
            ->assertJsonPath('items.0.routine_identifier', $firstRoutineIdentifier)
            ->assertJsonPath('items.0.published_at', '2026-09-03T09:00:00+00:00')
            ->assertJsonPath('items.0.posted_at', '2026-09-03T08:00:00+00:00');

        $this->getJson('/api/routines/search?title=ページ検索&page=3&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('items.0.routine_identifier', $secondRoutineIdentifier)
            ->assertJsonPath('items.0.posted_at', null);
    }

    private function account(string $identifier, string $name, string $status = 'active', bool $available = true, string $visibility = 'public'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'visibility' => $visibility,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routine(string $identifier, string $accountIdentifier, string $name, string $createdAt): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $name,
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function routineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routineExecution(string $identifier, string $routineIdentifier, string $executorIdentifier, string $createdAt): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'executor_account_identifier' => $executorIdentifier,
            'executed_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function executionPost(string $identifier, string $routineIdentifier, string $routineExecutionIdentifier, string $createdAt): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => 'action',
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function routinePost(string $identifier, string $routineIdentifier, int $likeCount, string $createdAt): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => 'routine',
            'post_like_count' => $likeCount,
            'available' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
