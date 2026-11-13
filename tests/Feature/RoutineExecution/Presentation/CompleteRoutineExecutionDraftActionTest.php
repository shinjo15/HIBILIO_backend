<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Infrastructure\Repository\AccountRepository;
use Src\RoutineExecution\Infrastructure\Repository\RoutineExecutionDraftRepository;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Tests\TestCase;

final class CompleteRoutineExecutionDraftActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const DRAFT = '44444444-4444-4444-8444-444444444444';

    private const ACTION = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

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
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::OTHER]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => self::ACTION, 'routine_identifier' => self::ROUTINE, 'action_name' => 'Action', 'position' => 0]);
        DB::table('routine_execution_drafts')->insert(['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => 'memo']);
        DB::table('routine_execution_action_drafts')->insert(['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION]);
    }

    public function test_completes_the_current_draft_and_records_post_and_experience(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routines/'.self::ROUTINE.'/execution-draft/complete')->assertCreated();
        $this->assertDatabaseHas('routine_executions', ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => 'memo']);
        $this->assertDatabaseHas('routine_execution_actions', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION]);
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['routine_identifier' => self::ROUTINE, 'routine_execution_identifier' => self::DRAFT, 'post_category' => 'action']);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 100]);
        $this->assertDatabaseCount('routine_execution_drafts', 0);
        $this->assertDatabaseCount('routine_execution_action_drafts', 0);
    }

    public function test_repeat_missing_and_other_accounts_cannot_complete_the_draft(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/complete';
        $this->postJson($url)->assertUnauthorized();
        $this->withSession(['account_identifier' => self::OTHER])->postJson($url, ['executor_account_identifier' => self::ACCOUNT])->assertNotFound();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson($url)->assertCreated();
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson($url)->assertNotFound();
        $this->assertDatabaseCount('routine_executions', 1);
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 100]);
    }

    public function test_null_memo_and_empty_actions_are_transferred(): void
    {
        DB::table('routine_execution_drafts')->update(['routine_execution_memo' => null]);
        DB::table('routine_execution_action_drafts')->delete();
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routines/'.self::ROUTINE.'/execution-draft/complete')->assertCreated();
        $this->assertDatabaseHas('routine_executions', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => null]);
        $this->assertDatabaseCount('routine_execution_actions', 0);
    }

    public function test_all_draft_actions_are_transferred(): void
    {
        $second = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        DB::table('routine_actions')->insert(['routine_action_identifier' => $second, 'routine_identifier' => self::ROUTINE, 'action_name' => 'Second', 'position' => 1]);
        DB::table('routine_execution_action_drafts')->insert(['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => $second]);
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routines/'.self::ROUTINE.'/execution-draft/complete')->assertCreated();
        $this->assertDatabaseCount('routine_execution_actions', 2);
        $this->assertDatabaseHas('routine_execution_actions', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION]);
        $this->assertDatabaseHas('routine_execution_actions', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => $second]);
    }

    public function test_failure_after_draft_deletion_rolls_back_everything(): void
    {
        $repository = \Mockery::mock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->andReturnUsing(fn ($account, $routine) => $this->app->make(RoutineExecutionDraftRepository::class)->find($account, $routine));
        $repository->shouldReceive('delete')->once()->andReturnUsing(function ($draft): void {
            $this->app->make(RoutineExecutionDraftRepository::class)->delete($draft);
            throw new \RuntimeException('delete failure');
        });
        $this->app->instance(RoutineExecutionDraftRepositoryInterface::class, $repository);
        $this->assertFailurePreservesDraft();
    }

    public function test_post_failure_rolls_back_and_preserves_draft(): void
    {
        $repository = \Mockery::mock(PostRepositoryInterface::class);
        $repository->shouldReceive('save')->once()->andThrow(new \RuntimeException('post failure'));
        $this->app->instance(PostRepositoryInterface::class, $repository);
        $this->assertFailurePreservesDraft();
    }

    public function test_experience_failure_rolls_back_post_and_preserves_draft(): void
    {
        $repository = \Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->andReturnUsing(fn ($id) => $this->app->make(AccountRepository::class)->find($id));
        $repository->shouldReceive('save')->once()->andThrow(new \RuntimeException('experience failure'));
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->assertFailurePreservesDraft();
    }

    public function test_existing_execution_identifier_prevents_duplicate_side_effects(): void
    {
        DB::table('routine_executions')->insert(['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00']);

        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routines/'.self::ROUTINE.'/execution-draft/complete')->assertStatus(500);
        $this->assertDatabaseCount('routine_executions', 1);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 0]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION]);
    }

    private function assertFailurePreservesDraft(): void
    {
        $this->withSession(['account_identifier' => self::ACCOUNT])->postJson('/api/routines/'.self::ROUTINE.'/execution-draft/complete')->assertStatus(500);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => 'memo']);
        $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::ACTION]);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 0]);
    }
}
