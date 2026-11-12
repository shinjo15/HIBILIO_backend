<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInputPort;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInterface;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\RoutineExecution\Infrastructure\Repository\AccountRepository;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CreateRoutineExecutionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_error_returns_server_error_and_rolls_back(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $action = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $account]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'Action', 'position' => 0]);

        $repository = \Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findWithLock')->once()->andReturnUsing(fn ($id) => $this->app->make(AccountRepository::class)->findWithLock($id));
        $repository->shouldReceive('save')->once()->andReturnUsing(fn () => DB::statement('insert into nonexistent_experience_test_table (id) values (1)'));
        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-executions', [
            'routine_identifier' => $routine,
            'executed_routine_action_identifiers' => [$action],
        ])->assertStatus(500);

        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 0]);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_missing_executor_returns_unprocessable_without_saving_execution_actions_or_post(): void
    {
        $author = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $action = '33333333-3333-4333-8333-333333333333';
        $missingExecutor = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert(['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $author]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'Action', 'position' => 0]);
        $auth = $this->createStub(AuthServiceInterface::class);
        $auth->method('accountIdentifier')->willReturn($missingExecutor);
        $this->app->instance(AuthServiceInterface::class, $auth);

        $this->postJson('/api/routine-executions', [
            'routine_identifier' => $routine,
            'executed_routine_action_identifiers' => [$action],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_unauthenticated_execution_returns_unauthorized(): void
    {
        $this->postJson('/api/routine-executions', [
            'routine_identifier' => '22222222-2222-4222-8222-222222222222',
            'executed_routine_action_identifiers' => ['33333333-3333-4333-8333-333333333333'],
        ])->assertUnauthorized();
    }

    public function test_successful_executions_increase_the_executors_experience(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $action = '33333333-3333-4333-8333-333333333333';
        $author = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('accounts')->insert(['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $author]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'Action', 'position' => 0]);
        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-executions', [
            'routine_identifier' => $routine,
            'executed_routine_action_identifiers' => [$action],
        ])->assertCreated();
        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 100]);
        $this->assertDatabaseCount('routine_executions', 1);
        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-executions', [
            'routine_identifier' => $routine,
            'executed_routine_action_identifiers' => [$action],
        ])->assertCreated();
        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 200]);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $author, 'experience' => 0]);
        $this->assertDatabaseCount('routine_executions', 2);
    }

    public function test_failure_to_save_experience_rolls_back_execution_and_post(): void
    {
        $repository = \Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findWithLock')->once()->andReturnUsing(fn ($id) => $this->app->make(AccountRepository::class)->findWithLock($id));
        $repository->shouldReceive('save')->once()->andThrow(new \Exception('experience failure'));
        $this->app->instance(AccountRepositoryInterface::class, $repository);
        $this->assertInjectedFailureRollsBack('experience failure');
    }

    public function test_failure_to_save_execution_does_not_add_experience(): void
    {
        $repository = \Mockery::mock(RoutineExecutionRepositoryInterface::class);
        $repository->shouldReceive('save')->once()->andThrow(new \Exception('execution failure'));
        $this->app->instance(RoutineExecutionRepositoryInterface::class, $repository);
        $this->assertInjectedFailureRollsBack('execution failure');
    }

    public function test_failure_to_save_post_does_not_record_execution_or_experience(): void
    {
        $repository = \Mockery::mock(PostRepositoryInterface::class);
        $repository->shouldReceive('save')->once()->andThrow(new \Exception('post failure'));
        $this->app->instance(PostRepositoryInterface::class, $repository);
        $this->assertInjectedFailureRollsBack('post failure');
    }

    private function assertInjectedFailureRollsBack(string $message): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $action = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $account]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'Action', 'position' => 0]);
        $this->withoutExceptionHandling();
        $caught = null;
        try {
            $this->withSession(['account_identifier' => $account])->postJson('/api/routine-executions', [
                'routine_identifier' => $routine,
                'executed_routine_action_identifiers' => [$action],
            ]);
        } catch (\Exception $exception) {
            $caught = $exception;
        }
        self::assertNotNull($caught);
        self::assertSame(\Exception::class, $caught::class);
        self::assertSame($message, $caught->getMessage());
        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 0]);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_rejects_an_empty_list_of_executed_routine_actions_without_executing_the_use_case(): void
    {
        $createRoutineExecution = new class implements CreateRoutineExecutionInterface
        {
            public int $executionCount = 0;

            public function execute(CreateRoutineExecutionInputPort $input): void
            {
                $this->executionCount++;
            }
        };
        $this->app->instance(CreateRoutineExecutionInterface::class, $createRoutineExecution);
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
            }
        });
        $this->postJson('/api/routine-executions', [
            'routine_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'executed_routine_action_identifiers' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('executed_routine_action_identifiers');
        self::assertSame(0, $createRoutineExecution->executionCount);
    }
}
