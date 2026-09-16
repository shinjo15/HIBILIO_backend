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
        $this->routineExecution($executionIdentifier, $routineIdentifier, $executorIdentifier, '2026-09-03 10:00:00');
        $this->executionPost('55555555-5555-4555-8555-555555555555', $routineIdentifier, $executionIdentifier, '2026-09-03 11:00:00');

        $this->getJson('/api/routines/search?title=ストレ&page=1&number_of_items_per_page=20')
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
                    ],
                ],
                'total' => 2,
            ]);
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

    public function test_paginates_the_combined_items(): void
    {
        $authorIdentifier = '11111111-1111-4111-8111-111111111111';
        $firstRoutineIdentifier = '22222222-2222-4222-8222-222222222222';
        $secondRoutineIdentifier = '33333333-3333-4333-8333-333333333333';

        $this->account($authorIdentifier, '作成者');
        $this->routine($firstRoutineIdentifier, $authorIdentifier, 'ページ検索一', '2026-09-03 09:00:00');
        $this->routine($secondRoutineIdentifier, $authorIdentifier, 'ページ検索二', '2026-09-02 09:00:00');

        $this->getJson('/api/routines/search?title=ページ検索&page=2&number_of_items_per_page=1')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.routine_identifier', $secondRoutineIdentifier)
            ->assertJsonCount(1, 'items');
    }

    private function account(string $identifier, string $name, string $status = 'active', bool $available = true): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
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
}
