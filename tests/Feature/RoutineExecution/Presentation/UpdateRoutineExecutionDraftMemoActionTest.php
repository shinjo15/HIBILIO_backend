<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\TestCase;

final class UpdateRoutineExecutionDraftMemoActionTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const DRAFT = '44444444-4444-4444-8444-444444444444';

    private const OTHER_DRAFT = '55555555-5555-4555-8555-555555555555';

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
        DB::table('routine_execution_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => '前'],
            ['routine_execution_identifier' => self::OTHER_DRAFT, 'executor_account_identifier' => self::OTHER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-02-01 12:00:00', 'routine_execution_memo' => '他人'],
        ]);
    }

    public function test_saves_and_clears_memo_without_changing_other_draft_or_time(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/memo';
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, ['routine_execution_memo' => str_repeat('a', 31)])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => str_repeat('a', 31)]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_execution_memo' => '他人']);
        $this->patchJson($url, ['routine_execution_memo' => null])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => null]);
    }

    public function test_updating_a_deleted_draft_does_not_resurrect_it(): void
    {
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();

        try {
            $draft->changeMemo(new RoutineExecutionMemo('変更'));
            $repository->updateMemo($draft);
            self::fail('A deleted draft must not be recreated.');
        } catch (RoutineExecutionDraftNotFoundException) {
            $this->assertDatabaseMissing('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        }
    }

    public function test_validates_required_nullable_memo_and_length(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/memo';
        foreach ([[], ['routine_execution_memo' => str_repeat('あ', 32)], ['routine_execution_memo' => 123]] as $body) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, $body)->assertUnprocessable();
        }
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => '前']);
    }

    public function test_recreated_draft_with_new_identity_is_not_updated_by_stale_entity(): void
    {
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();
        $replacement = '88888888-8888-4888-8888-888888888888';
        DB::table('routine_execution_drafts')->insert(['routine_execution_identifier' => $replacement, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-03-01 12:00:00', 'routine_execution_memo' => '新規']);

        try {
            $draft->changeMemo(new RoutineExecutionMemo('古い変更'));
            $repository->updateMemo($draft);
            self::fail('The original draft no longer exists.');
        } catch (RoutineExecutionDraftNotFoundException) {
            $this->assertDatabaseMissing('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
            $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => $replacement, 'routine_execution_memo' => '新規']);
        }
    }

    public function test_update_preserves_fields_changed_since_retrieval(): void
    {
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->update(['executed_at' => '2026-04-01 12:00:00', 'updated_at' => '2026-04-02 12:00:00']);
        $draft->changeMemo(new RoutineExecutionMemo('変更'));
        $repository->updateMemo($draft);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'executed_at' => '2026-04-01 12:00:00', 'updated_at' => '2026-04-02 12:00:00', 'routine_execution_memo' => '変更']);
    }

    public function test_update_does_not_match_another_owner_even_with_same_identifier(): void
    {
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        $wrongOwner = new RoutineExecutionDraft($draft->routineExecutionIdentifier(), new AccountIdentifier(self::OTHER), $draft->routineIdentifier(), $draft->executedAt(), new RoutineExecutionMemo('変更'));
        try {
            $repository->updateMemo($wrongOwner);
            self::fail('The draft does not belong to this account.');
        } catch (RoutineExecutionDraftNotFoundException) {
            $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => '前']);
        }
    }

    public function test_same_memo_and_null_can_be_repeated(): void
    {
        $url = '/api/routines/'.self::ROUTINE.'/execution-draft/memo';
        foreach (['前', '前', null, null] as $memo) {
            $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson($url, ['routine_execution_memo' => $memo])->assertNoContent();
            $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => $memo]);
        }
    }

    public function test_zero_affected_rows_for_existing_draft_is_not_treated_as_missing(): void
    {
        DB::unprepared('CREATE TRIGGER ignore_same_memo BEFORE UPDATE ON routine_execution_drafts WHEN NEW.routine_execution_memo IS OLD.routine_execution_memo BEGIN SELECT RAISE(IGNORE); END');
        $repository = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $draft = $repository->find(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        self::assertNotNull($draft);
        $repository->updateMemo($draft);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => '前']);
    }

    public function test_api_returns_not_found_when_draft_is_deleted_after_retrieval(): void
    {
        $real = $this->app->make(RoutineExecutionDraftRepositoryInterface::class);
        $repository = $this->createStub(RoutineExecutionDraftRepositoryInterface::class);
        $repository->method('find')->willReturnCallback(function (AccountIdentifier $account, RoutineIdentifier $routine) use ($real): ?RoutineExecutionDraft {
            $draft = $real->find($account, $routine);
            DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();

            return $draft;
        });
        $repository->method('updateMemo')->willReturnCallback(fn (RoutineExecutionDraft $draft) => $real->updateMemo($draft));
        $this->app->instance(RoutineExecutionDraftRepositoryInterface::class, $repository);
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/memo', ['routine_execution_memo' => '変更'])->assertNotFound();
        $this->assertDatabaseMissing('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT]);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_execution_memo' => '他人']);
    }

    public function test_unauthenticated_is_rejected(): void
    {
        $this->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/memo', ['routine_execution_memo' => '変更'])->assertUnauthorized();
    }

    public function test_another_users_draft_is_not_accessible_when_own_draft_missing(): void
    {
        DB::table('routine_execution_drafts')->where('routine_execution_identifier', self::DRAFT)->delete();
        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/memo', ['routine_execution_memo' => '変更'])->assertNotFound();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_execution_memo' => '他人']);
    }

    public function test_does_not_update_another_routine_and_ignores_body_identity(): void
    {
        $otherRoutine = '66666666-6666-4666-8666-666666666666';
        $otherDraft = '77777777-7777-4777-8777-777777777777';
        DB::table('routines')->insert(['routine_identifier' => $otherRoutine, 'routine_name' => 'Other', 'account_identifier' => self::ACCOUNT]);
        DB::table('routine_execution_drafts')->insert(['routine_execution_identifier' => $otherDraft, 'executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => $otherRoutine, 'executed_at' => '2026-03-01 12:00:00', 'routine_execution_memo' => '別']);

        $this->withSession(['account_identifier' => self::ACCOUNT])->patchJson('/api/routines/'.self::ROUTINE.'/execution-draft/memo', [
            'routine_identifier' => $otherRoutine,
            'executor_account_identifier' => self::OTHER,
            'routine_execution_memo' => '更新',
        ])->assertNoContent();
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::DRAFT, 'routine_execution_memo' => '更新']);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => $otherDraft, 'routine_execution_memo' => '別']);
        $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_execution_memo' => '他人']);
    }
}
