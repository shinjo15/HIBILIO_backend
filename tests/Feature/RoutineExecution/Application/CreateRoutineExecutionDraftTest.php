<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Application;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftAlreadyExistsException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\TestCase;

final class CreateRoutineExecutionDraftTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '11111111-1111-4111-8111-111111111111';

    private const ROUTINE = '22222222-2222-4222-8222-222222222222';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite' || $app['config']->get('database.connections.sqlite.database') !== ':memory:' || $app['config']->get('database.connections.sqlite.url')) {
            throw new \RuntimeException('Tests require an isolated in-memory SQLite database.');
        }

        return $app;
    }

    public function test_creates_only_an_empty_execution_draft(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::ACCOUNT]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_identifier' => self::ROUTINE, 'action_name' => 'Action', 'position' => 0]);

        $result = $this->app->make(CreateRoutineExecutionDraftInterface::class)->execute(new CreateRoutineExecutionDraftInput(
            new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE),
        ));

        self::assertNull($result);
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::ACCOUNT, 'routine_identifier' => self::ROUTINE, 'routine_execution_memo' => null]);
        self::assertNotNull(DB::table('routine_execution_drafts')->value('executed_at'));
        $this->assertDatabaseCount('routine_execution_action_drafts', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACCOUNT, 'experience' => 0]);
    }

    public function test_distinct_accounts_and_routines_have_independent_drafts(): void
    {
        $otherAccount = '44444444-4444-4444-8444-444444444444';
        $otherRoutine = '55555555-5555-4555-8555-555555555555';
        DB::table('accounts')->insert([
            ['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com'],
            ['account_identifier' => $otherAccount, 'account_name' => 'Other', 'email_address' => 'other@example.com'],
        ]);
        DB::table('routines')->insert([
            ['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::ACCOUNT],
            ['routine_identifier' => $otherRoutine, 'routine_name' => 'Other', 'account_identifier' => self::ACCOUNT],
        ]);
        $useCase = $this->app->make(CreateRoutineExecutionDraftInterface::class);
        foreach ([[self::ACCOUNT, self::ROUTINE], [self::ACCOUNT, $otherRoutine], [$otherAccount, self::ROUTINE]] as [$account, $routine]) {
            $useCase->execute(new CreateRoutineExecutionDraftInput(new AccountIdentifier($account), new RoutineIdentifier($routine)));
            $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => $account, 'routine_identifier' => $routine]);
        }
        $this->assertDatabaseCount('routine_execution_drafts', 3);
    }

    public function test_duplicate_insert_raises_a_domain_error_without_changing_the_existing_draft(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::ACCOUNT]);
        $useCase = $this->app->make(CreateRoutineExecutionDraftInterface::class);
        $input = new CreateRoutineExecutionDraftInput(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE));
        $useCase->execute($input);
        DB::table('routine_execution_drafts')->update(['routine_execution_memo' => '既存']);
        $original = DB::table('routine_execution_drafts')->first();

        try {
            $useCase->execute($input);
            self::fail('Duplicate draft must not be inserted.');
        } catch (RoutineExecutionDraftAlreadyExistsException) {
            $this->assertDatabaseCount('routine_execution_drafts', 1);
            self::assertEquals($original, DB::table('routine_execution_drafts')->first());
        }
    }

    public function test_missing_routine_raises_a_not_found_error(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::ACCOUNT, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        try {
            $this->app->make(CreateRoutineExecutionDraftInterface::class)->execute(
                new CreateRoutineExecutionDraftInput(new AccountIdentifier(self::ACCOUNT), new RoutineIdentifier(self::ROUTINE)),
            );
            self::fail('A draft cannot reference a missing routine.');
        } catch (RoutineNotFoundException $exception) {
            self::assertInstanceOf(RoutineNotFoundException::class, $exception);
            $this->assertDatabaseCount('routine_execution_drafts', 0);
        }
    }
}
