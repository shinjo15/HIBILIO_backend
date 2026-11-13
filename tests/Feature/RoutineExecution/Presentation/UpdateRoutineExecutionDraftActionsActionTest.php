<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\TestCase;

final class UpdateRoutineExecutionDraftActionsActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const OTHER_ROUTINE = '44444444-4444-4444-8444-444444444444';

    private const DRAFT = '55555555-5555-4555-8555-555555555555';

    private const OTHER_DRAFT = '66666666-6666-4666-8666-666666666666';

    private const A = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const B = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const C = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

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
            ['routine_identifier' => self::OTHER_ROUTINE, 'routine_name' => 'Other', 'account_identifier' => self::OTHER],
        ]);
        DB::table('routine_actions')->insert([
            ['routine_action_identifier' => self::A, 'routine_identifier' => self::ROUTINE, 'action_name' => 'A', 'position' => 0],
            ['routine_action_identifier' => self::B, 'routine_identifier' => self::ROUTINE, 'action_name' => 'B', 'position' => 1],
            ['routine_action_identifier' => self::C, 'routine_identifier' => self::OTHER_ROUTINE, 'action_name' => 'C', 'position' => 0],
        ]);
        DB::table('routine_execution_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => 'memo'],
            ['routine_execution_identifier' => self::OTHER_DRAFT, 'executor_account_identifier' => self::OTHER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => null],
        ]);
        DB::table('routine_execution_action_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A],
            ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A],
        ]);
    }

    public function test_replaces_the_draft_actions(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/actions', ['executed_routine_action_identifiers' => [self::B]])->assertNoContent();
        $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::B]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => 'memo']);
    }

    public function test_full_partial_and_empty_replacement(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/actions';
        foreach ([[self::A, self::B], [self::B], [], []] as $ids) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, ['executed_routine_action_identifiers' => $ids])->assertNoContent();
            $stored = DB::table('routine_execution_action_drafts')->where('routine_execution_identifier', self::DRAFT)->orderBy('routine_action_identifier')->pluck('routine_action_identifier')->all();
            self::assertSame($ids, $stored);
        }
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A]);
    }

    public function test_invalid_input_and_unrelated_actions_leave_existing_set_unchanged(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/actions';
        foreach ([[], ['executed_routine_action_identifiers' => null], ['executed_routine_action_identifiers' => 'bad'], ['executed_routine_action_identifiers' => ['bad']], ['executed_routine_action_identifiers' => [self::A, self::A]], ['executed_routine_action_identifiers' => [self::A, strtoupper(self::A)]], ['executed_routine_action_identifiers' => [self::C]], ['executed_routine_action_identifiers' => ['dddddddd-dddd-4ddd-8ddd-dddddddddddd']]] as $body) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, $body)->assertUnprocessable();
            $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A]);
            self::assertSame(1, DB::table('routine_execution_action_drafts')->where('routine_execution_identifier', self::DRAFT)->count());
        }
    }

    public function test_json_objects_are_not_accepted_as_action_arrays(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/actions';
        foreach (['{}', '{"0":"'.self::B.'"}'] as $value) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->call(
                'PATCH', $url, [], [], [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                '{"executed_routine_action_identifiers":'.$value.'}',
            )->assertUnprocessable()->assertJsonValidationErrors('executed_routine_action_identifiers');
            $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A]);
            self::assertSame(1, DB::table('routine_execution_action_drafts')->where('routine_execution_identifier', self::DRAFT)->count());
        }
    }

    public function test_form_encoded_action_array_remains_supported(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->patch('/api/routines/'.self::ROUTINE.'/execution-draft/actions', [
            'executed_routine_action_identifiers' => [self::B],
        ])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::B]);
    }

    public function test_authentication_and_other_users_draft_are_protected(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/actions';
        $body = ['executed_routine_action_identifiers' => [self::B]];
        $this->patchJson($url, $body)->assertUnauthorized();
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, $body + ['executor_account_identifier' => self::OTHER])->assertNotFound();
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A]);
        $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::B]);
    }

    public function test_body_cannot_override_session_or_route_identity(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/actions', [
            'executor_account_identifier' => self::OTHER,
            'routine_identifier' => self::OTHER_ROUTINE,
            'executed_routine_action_identifiers' => [self::B],
        ])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::B]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A]);
    }

    public function test_insert_failure_rolls_back_deletion_and_partial_insert(): void
    {
        DB::unprepared("CREATE TRIGGER fail_action_insert BEFORE INSERT ON routine_execution_action_drafts WHEN NEW.routine_action_identifier = '".self::B."' BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/actions', ['executed_routine_action_identifiers' => [self::A, self::B]])->assertServerError();
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A]);
        $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::B]);
        self::assertSame(1, DB::table('routine_execution_action_drafts')->where('routine_execution_identifier', self::DRAFT)->count());
    }

    public function test_repository_restores_actions_and_does_not_update_a_deleted_draft(): void
    {
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        self::assertSame(self::A, $draft->actions()[0]->routineActionIdentifier()->value());
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();
        $this->expectException(RoutineExecutionDraftNotFoundException::class);
        $repository->updateActions($draft);
    }
}
