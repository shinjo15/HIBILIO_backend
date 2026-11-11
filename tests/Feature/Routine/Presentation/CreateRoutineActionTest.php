<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use App\Models\TagModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class CreateRoutineActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_youtube_video_is_rejected_without_creating_records(): void
    {
        $this->insertActiveAccount();
        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])->postJson('/api/routines', [
            'routine_name' => 'routine',
            'video_url' => 'https://example.com/video',
            'routine_actions' => [['routine_action_name' => 'action']],
        ])->assertUnprocessable()->assertExactJson([
            'message' => 'YouTubeの動画URLを指定してください。',
            'errors' => ['video_url' => ['YouTubeの動画URLを指定してください。']],
        ]);
        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('routine_actions', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_creates_a_routine_for_the_account_identifier_in_the_session(): void
    {
        $this->insertActiveAccount();
        TagModel::query()->create([
            'tag_identifier' => 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            'tag_name' => '朝活',
        ]);

        $response = $this->withSession([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
        ])->postJson('/api/routines', [
            'account_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routine_name' => '朝の集中ルーティン',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'routine_memo' => '仕事前に集中力を高める',
            'routine_execution_minutes' => 40,
            'tag_identifiers' => [
                'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5',
            ],
            'routine_actions' => [
                [
                    'routine_action_name' => '水を飲む',
                    'routine_action_minutes' => 1,
                ],
                [
                    'routine_action_name' => 'ストレッチ',
                    'parent_routine_action_index' => 0,
                    'routine_action_minutes' => 5,
                ],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('routines', [
            'routine_name' => '朝の集中ルーティン',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
        ]);
        $this->assertDatabaseMissing('routines', [
            'account_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]);
    }

    public function test_returns_unauthorized_when_the_session_has_no_account_identifier(): void
    {
        $this->postJson('/api/routines', [
            'routine_name' => '朝の集中ルーティン',
            'routine_actions' => [
                [
                    'routine_action_name' => '水を飲む',
                ],
            ],
        ])->assertUnauthorized();
    }

    public function test_creates_a_routine_that_references_the_specified_parent_routine(): void
    {
        $this->insertActiveAccount();
        DB::table('routines')->insert([
            'routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'routine_name' => '元ルーティン',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ?source=parent',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
        ])->postJson('/api/routines', [
            'parent_routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routine_name' => 'カスタマイズ版',
            'routine_actions' => [[
                'routine_action_name' => '水を飲む',
            ]],
        ])->assertCreated();

        $this->assertDatabaseHas('routines', [
            'parent_routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routine_name' => 'カスタマイズ版',
            'video_url' => null,
        ]);
    }

    public function test_invalid_video_urls_are_rejected_before_creating_any_records(): void
    {
        $this->insertActiveAccount();
        foreach (['', '0', 0, 'javascript:alert(1)', 'https://127.0.0.999/', 'https://999.999.999.999/', 'https://1.2.3.08/', 'https://999999999999/', 'https://1.16777216/', 'https://example.com/'.str_repeat('a', 2030)] as $invalid) {
            $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])->postJson('/api/routines', [
                'routine_name' => 'routine',
                'routine_actions' => [['routine_action_name' => 'action']],
                'video_url' => $invalid,
            ])->assertUnprocessable();
        }
        $this->assertDatabaseCount('routines', 0);
        $this->assertDatabaseCount('routine_actions', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_creation_preserves_supported_youtube_urls(): void
    {
        $this->insertActiveAccount();
        foreach (['http://youtube.com/watch?v=dQw4w9WgXcQ&t=12', 'https://youtu.be/dQw4w9WgXcQ?si=abc', 'https://m.youtube.com/shorts/dQw4w9WgXcQ', 'https://youtube.com/live/dQw4w9WgXcQ?feature=share', 'https://www.youtube.com/embed/dQw4w9WgXcQ'] as $url) {
            $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])->postJson('/api/routines', [
                'routine_name' => 'routine',
                'routine_actions' => [['routine_action_name' => 'action', 'video_url' => $url]],
                'video_url' => $url,
            ])->assertCreated();
            $this->assertDatabaseHas('routines', ['video_url' => $url]);
            $this->assertDatabaseHas('routine_actions', ['video_url' => $url]);
        }
    }

    public function test_video_validation_does_not_change_other_field_normalization(): void
    {
        $this->insertActiveAccount();
        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])->postJson('/api/routines', [
            'routine_name' => ' routine ',
            'routine_memo' => '',
            'routine_actions' => [['routine_action_name' => ' action ']],
            'video_url' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['video_url'])->assertJsonMissingValidationErrors(['routine_name', 'routine_memo', 'routine_actions.0.routine_action_name']);
        $this->withSession(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87'])->postJson('/api/routines', [
            'routine_name' => ' routine ',
            'routine_memo' => '',
            'routine_actions' => [['routine_action_name' => ' action ']],
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ?title=日本語',
        ])->assertCreated();
        $this->assertDatabaseHas('routines', ['routine_name' => 'routine', 'routine_memo' => null, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?title=日本語']);
    }

    public function test_unrelated_routes_keep_default_video_field_normalization(): void
    {
        Route::post('/unrelated-normalization', static fn (Request $request) => response()->json($request->all()));
        $this->postJson('/unrelated-normalization', ['video_url' => '  value  '])->assertOk()->assertJsonPath('video_url', 'value');
    }

    private function insertActiveAccount(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'account_name' => 'テストAccount',
            'email_address' => 'routine-creator@example.com',
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
