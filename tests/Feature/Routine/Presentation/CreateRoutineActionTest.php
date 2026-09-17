<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use App\Models\TagModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateRoutineActionTest extends TestCase
{
    use RefreshDatabase;

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
        ]);
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
