<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Infrastructure\Repository\RoutineExecutionDraftRepository;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Tests\TestCase;

final class CreateRoutineExecutionDraftActionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('accountChangesAfterAuthentication')]
    public function test_account_invalidated_after_authentication_cannot_create_draft(array $changes): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $author = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('accounts')->insert(['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $author]);
        $this->app->instance(TransactionManagerInterface::class, new class($account, $changes) implements TransactionManagerInterface
        {
            public function __construct(private string $account, private array $changes) {}

            public function transaction(callable $callback): mixed
            {
                if ($this->changes === []) {
                    DB::table('accounts')->where('account_identifier', $this->account)->delete();
                } else {
                    DB::table('accounts')->where('account_identifier', $this->account)->update($this->changes);
                }

                return DB::transaction($callback);
            }
        });

        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertUnauthorized();
        $this->assertDatabaseCount('routine_execution_drafts', 0);
    }

    public static function accountChangesAfterAuthentication(): array
    {
        return [
            'unavailable' => [['available' => false]],
            'temporarily banned' => [['status' => 'temporarily_banned']],
            'permanently banned' => [['status' => 'permanently_banned']],
            'deleted' => [[]],
        ];
    }

    public function test_creates_only_one_draft_without_recording_execution(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'user@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $account]);

        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
        $draft = DB::table('routine_execution_drafts')->first();
        self::assertSame($account, $draft->executor_account_identifier);
        self::assertSame($routine, $draft->routine_identifier);
        self::assertNull($draft->routine_execution_memo);
        self::assertNotNull($draft->executed_at);
        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertNoContent();
        self::assertSame($draft->routine_execution_identifier, DB::table('routine_execution_drafts')->value('routine_execution_identifier'));
        $this->assertDatabaseCount('routine_execution_drafts', 1);
        $this->assertDatabaseCount('routine_execution_draft_actions', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 0]);
    }

    public function test_authentication_validation_and_separate_accounts_and_routines(): void
    {
        $a = '11111111-1111-4111-8111-111111111111';
        $b = '44444444-4444-4444-8444-444444444444';
        $r = '22222222-2222-4222-8222-222222222222';
        $s = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert([
            ['account_identifier' => $a, 'account_name' => 'A', 'email_address' => 'a@example.com'],
            ['account_identifier' => $b, 'account_name' => 'B', 'email_address' => 'b@example.com'],
        ]);
        DB::table('routines')->insert([
            ['routine_identifier' => $r, 'routine_name' => 'R', 'account_identifier' => $a],
            ['routine_identifier' => $s, 'routine_name' => 'S', 'account_identifier' => $a],
        ]);
        $this->postJson('/api/routine-execution-drafts', ['routine_identifier' => $r])->assertUnauthorized();
        $this->postJson('/api/routine-execution-drafts', ['routine_identifier' => 'invalid'])->assertUnauthorized();
        $this->withSession(['account_identifier' => $a])->postJson('/api/routine-execution-drafts', ['routine_identifier' => 'not-a-uuid'])->assertUnprocessable();
        $this->withSession(['account_identifier' => $a])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $r, 'executor_account_identifier' => $b])->assertCreated();
        $this->withSession(['account_identifier' => $a])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $s])->assertCreated();
        $this->withSession(['account_identifier' => $b])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $r])->assertCreated();
        $this->assertDatabaseCount('routine_execution_drafts', 3);
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => $a, 'routine_identifier' => $r]);
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => $b, 'routine_identifier' => $r]);
    }

    public function test_private_blocked_unavailable_and_missing_routines_cannot_be_executed(): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $author = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com'],
            ['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $author]);
        $post = fn (string $id) => $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $id]);
        $post('33333333-3333-4333-8333-333333333333')->assertNotFound();
        DB::table('routines')->insert(['routine_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_name' => 'Orphan', 'account_identifier' => '55555555-5555-4555-8555-555555555555']);
        $post('33333333-3333-4333-8333-333333333333')->assertForbidden();
        DB::table('routines')->where('routine_identifier', $routine)->update(['available' => false]);
        $post($routine)->assertForbidden();
        DB::table('routines')->where('routine_identifier', $routine)->update(['available' => true]);
        DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
        $post($routine)->assertForbidden();
        DB::table('follows')->insert(['following_account_identifier' => $author, 'followed_account_identifier' => $viewer]);
        $post($routine)->assertForbidden();
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $author]);
        DB::table('blocks')->insert(['blocking_account_identifier' => $author, 'blocked_account_identifier' => $viewer]);
        $post($routine)->assertForbidden();
        DB::table('blocks')->delete();
        DB::table('blocks')->insert(['blocking_account_identifier' => $viewer, 'blocked_account_identifier' => $author]);
        $post($routine)->assertForbidden();
        DB::table('blocks')->delete();
        DB::table('accounts')->where('account_identifier', $author)->update(['available' => false]);
        $post($routine)->assertForbidden();
        DB::table('accounts')->where('account_identifier', $author)->update(['available' => true, 'status' => 'temporarily_banned']);
        $post($routine)->assertForbidden();
        DB::table('accounts')->where('account_identifier', $author)->update(['status' => 'active']);
        $this->withSession(['account_identifier' => $author])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
        $post($routine)->assertCreated();
        $this->assertDatabaseCount('routine_execution_drafts', 2);
    }

    public function test_visibility_is_evaluated_for_each_requesting_account(): void
    {
        $author = '11111111-1111-4111-8111-111111111111';
        $follower = '22222222-2222-4222-8222-222222222222';
        $stranger = '33333333-3333-4333-8333-333333333333';
        $routine = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert([
            ['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com', 'visibility' => 'private'],
            ['account_identifier' => $follower, 'account_name' => 'Follower', 'email_address' => 'follower@example.com', 'visibility' => 'public'],
            ['account_identifier' => $stranger, 'account_name' => 'Stranger', 'email_address' => 'stranger@example.com', 'visibility' => 'public'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $author]);
        DB::table('follows')->insert(['following_account_identifier' => $follower, 'followed_account_identifier' => $author]);

        $this->withSession(['account_identifier' => $stranger])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertForbidden();
        $this->withSession(['account_identifier' => $follower])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
        $this->withSession(['account_identifier' => $stranger])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertForbidden();
        $this->assertDatabaseCount('routine_execution_drafts', 1);
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => $follower, 'routine_identifier' => $routine]);
    }

    public function test_database_enforces_unique_account_and_routine(): void
    {
        $a = '11111111-1111-4111-8111-111111111111';
        $r = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $a, 'account_name' => 'A', 'email_address' => 'a@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $r, 'routine_name' => 'R', 'account_identifier' => $a]);
        $this->withSession(['account_identifier' => $a])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $r])->assertCreated();
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('routine_execution_drafts')->insert(['routine_execution_identifier' => '33333333-3333-4333-8333-333333333333', 'executor_account_identifier' => $a, 'routine_identifier' => $r, 'executed_at' => now()]);
    }

    public function test_save_failure_rolls_back_the_draft(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'A', 'email_address' => 'a@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'R', 'account_identifier' => $account]);
        $repository = \Mockery::mock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturnFalse();
        $repository->shouldReceive('save')->once()->andReturnUsing(function ($draft): void {
            $this->app->make(RoutineExecutionDraftRepository::class)->save($draft);
            throw new \RuntimeException('injected failure');
        });
        $this->app->instance(RoutineExecutionDraftRepositoryInterface::class, $repository);
        $this->withSession(['account_identifier' => $account])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertServerError();
        $this->assertDatabaseCount('routine_execution_drafts', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $account, 'experience' => 0]);
    }
}
