<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateRoutineExecutionDraftMemoActionTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([self::OWNER, self::OTHER] as $i => $account) {
            DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => "$i@example.com"]);
        }
        DB::table('routine_execution_drafts')->insert(['draft_creator_account_identifier' => self::OWNER, 'routine_identifier' => self::ROUTINE, 'account_identifier' => self::OWNER, 'routine_name' => 'Routine', 'routine_memo' => 'source description', 'available' => true]);
    }

    public function test_owner_saves_execution_memo_without_changing_source_description(): void
    {
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => str_repeat('あ', 31)])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_identifier' => self::ROUTINE, 'routine_memo' => 'source description', 'routine_execution_memo' => str_repeat('あ', 31)]);
    }

    public function test_owner_can_clear_memo(): void
    {
        DB::table('routine_execution_drafts')->update(['routine_execution_memo' => 'old']);
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => null])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_memo' => 'source description', 'routine_execution_memo' => null]);
    }

    public function test_repeated_same_memo_and_null_are_successful(): void
    {
        $url = '/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo';
        foreach (['same', 'same', null, null] as $memo) {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson($url, ['routine_execution_memo' => $memo])->assertNoContent();
            $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_identifier' => self::ROUTINE, 'routine_execution_memo' => $memo]);
        }
    }

    public function test_whitespace_is_trimmed_and_blank_memo_becomes_null(): void
    {
        $url = '/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo';
        $this->withSession(['account_identifier' => self::OWNER])->patchJson($url, ['routine_execution_memo' => '  memo  '])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => 'memo']);
        $this->withSession(['account_identifier' => self::OWNER])->patchJson($url, ['routine_execution_memo' => '   '])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => null]);
    }

    public function test_unauthenticated_user_cannot_update(): void
    {
        $this->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => 'new'])->assertUnauthorized();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => null]);
    }

    public function test_other_owners_draft_is_not_disclosed_or_modified(): void
    {
        $this->withSession(['account_identifier' => self::OTHER])->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => 'new'])->assertNotFound();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => null]);
    }

    public function test_missing_draft_is_not_found(): void
    {
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/my/routine-execution-drafts/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb/memo', ['routine_execution_memo' => 'new'])->assertNotFound();
    }

    public function test_draft_deleted_before_update_is_not_reported_as_saved(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('This deletion trigger uses SQLite syntax.');
        }

        DB::unprepared('CREATE TRIGGER delete_draft_before_update BEFORE UPDATE ON routine_execution_drafts BEGIN DELETE FROM routine_execution_drafts WHERE draft_creator_account_identifier = OLD.draft_creator_account_identifier AND routine_identifier = OLD.routine_identifier; SELECT RAISE(IGNORE); END');

        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => 'new'])->assertNotFound();
        $this->assertDatabaseMissing('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_identifier' => self::ROUTINE]);
    }

    public function test_only_the_current_owners_copy_is_updated(): void
    {
        DB::table('routine_execution_drafts')->insert(['draft_creator_account_identifier' => self::OTHER, 'routine_identifier' => self::ROUTINE, 'account_identifier' => self::OWNER, 'routine_name' => 'Routine', 'available' => true, 'routine_execution_memo' => 'other']);
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo', ['routine_execution_memo' => 'mine'])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OTHER, 'routine_execution_memo' => 'other']);
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => 'mine']);
    }

    public function test_validation_requires_a_nullable_string_of_at_most_31_characters(): void
    {
        $url = '/api/my/routine-execution-drafts/'.self::ROUTINE.'/memo';
        foreach ([[], ['routine_execution_memo' => str_repeat('あ', 32)], ['routine_execution_memo' => ['invalid']]] as $body) {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson($url, $body)->assertUnprocessable()->assertJsonValidationErrors('routine_execution_memo');
        }
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => self::OWNER, 'routine_execution_memo' => null]);
    }
}
