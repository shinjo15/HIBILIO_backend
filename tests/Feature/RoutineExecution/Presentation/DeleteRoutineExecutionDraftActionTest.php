<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DeleteRoutineExecutionDraftActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const OTHER_ROUTINE = '44444444-4444-4444-8444-444444444444';

    private const DRAFT = '55555555-5555-4555-8555-555555555555';

    private const OTHER_ACCOUNT_DRAFT = '66666666-6666-4666-8666-666666666666';

    private const OTHER_ROUTINE_DRAFT = '77777777-7777-4777-8777-777777777777';

    private const ACTION = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const OTHER_ACTION = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite' || $app['config']->get('database.connections.sqlite.database') !== ':memory:' || $app['config']->get('database.connections.sqlite.url')) {
            throw new \RuntimeException('Tests require an isolated in-memory SQLite database.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('accounts')->insert([
            ['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com'],
            ['account_identifier' => self::OTHER, 'account_name' => 'Other', 'email_address' => 'other@example.com'],
        ]);
        DB::table('routines')->insert([
            ['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::OTHER],
            ['routine_identifier' => self::OTHER_ROUTINE, 'routine_name' => 'Other', 'account_identifier' => self::ACCOUNT],
        ]);
        DB::table('routine_actions')->insert([
            ['routine_action_identifier' => self::ACTION, 'routine_identifier' => self::ROUTINE, 'action_name' => 'Action', 'position' => 0],
            ['routine_action_identifier' => self::OTHER_ACTION, 'routine_identifier' => self::OTHER_ROUTINE, 'action_name' => 'Other', 'position' => 0],
        ]);
        DB::table('routine_execution_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00'],
            ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT, 'executor_account_identifier' => self::OTHER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00'],
            ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::OTHER_ROUTINE, 'executed_at' => '2026-01-01 12:00:00'],
        ]);
        DB::table('routine_execution_action_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION],
            ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT, 'routine_action_identifier' => self::ACTION],
            ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT, 'routine_action_identifier' => self::OTHER_ACTION],
        ]);
    }

    public function test_deletes_only_the_current_accounts_draft_and_its_actions(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->deleteJson('/api/routines/'.self::ROUTINE.'/execution-draft')->assertNoContent();

        $this->assertDatabaseMissing('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT, 'routine_action_identifier' => self::ACTION]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT, 'routine_action_identifier' => self::OTHER_ACTION]);
        $this->assertDatabaseCount('routines', 2);
        $this->assertDatabaseCount('routine_actions', 2);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 0]);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::OTHER, 'experience' => 0]);
    }

    public function test_authentication_and_identifier_validation_leave_drafts_unchanged(): void
    {
        $this->deleteJson('/api/routines/'.self::ROUTINE.'/execution-draft')->assertUnauthorized();
        $this->withSession(['account_identifier' => self::ACCOUNT])->deleteJson('/api/routines/invalid/execution-draft')->assertUnprocessable();
        $this->assertDatabaseCount('routine_execution_drafts', 3);
        $this->assertDatabaseCount('routine_execution_action_drafts', 3);
    }

    public function test_missing_and_other_accounts_drafts_return_not_found(): void
    {
        $url = '/api/routines/'.self::OTHER_ROUTINE.'/execution-draft';
        $this->withSession(['account_identifier' => self::OTHER])->deleteJson($url, ['executor_account_identifier' => self::ACCOUNT])->assertNotFound();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT]);
        $this->withSession(['account_identifier' => self::ACCOUNT])->deleteJson($url)->assertNoContent();
        $this->withSession(['account_identifier' => self::ACCOUNT])->deleteJson($url)->assertNotFound();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT]);
    }

    public function test_deleted_draft_can_be_restarted_using_the_create_endpoint(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->deleteJson('/api/routines/'.self::ROUTINE.'/execution-draft')->assertNoContent();
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertCreated();

        $newDraft = DB::table('routine_execution_drafts')->where('executor_account_identifier', self::ACCOUNT)->where('routine_identifier', self::ROUTINE)->first();
        self::assertNotNull($newDraft);
        self::assertNotSame(self::DRAFT, $newDraft->routine_execution_identifier);
        self::assertNull($newDraft->routine_execution_memo);
        $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => $newDraft->routine_execution_identifier]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ACCOUNT_DRAFT]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_ROUTINE_DRAFT]);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 0]);
    }
}
