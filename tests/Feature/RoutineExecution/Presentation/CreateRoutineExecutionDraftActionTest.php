<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Infrastructure\Repository\AccountRepository;
use Src\RoutineExecution\Infrastructure\Repository\RoutineExecutionDraftRepository;
use Tests\TestCase;

final class CreateRoutineExecutionDraftActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_routine_without_follow_is_forbidden_without_creating_a_draft(): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $owner = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com', 'visibility' => 'public'],
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'private'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $owner]);

        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertForbidden();
        DB::table('follows')->insert(['following_account_identifier' => $owner, 'followed_account_identifier' => $viewer]);
        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertForbidden();
        $this->assertDatabaseCount('routine_execution_drafts', 0);
    }

    #[DataProvider('inaccessibleRoutineCases')]
    public function test_inaccessible_or_missing_routine_does_not_change_existing_data(string $case, int $status): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $owner = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com'],
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $owner]);
        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();

        if ($case === 'viewer_blocks_owner' || $case === 'owner_blocks_viewer') {
            DB::table('blocks')->insert([
                'blocking_account_identifier' => $case === 'viewer_blocks_owner' ? $viewer : $owner,
                'blocked_account_identifier' => $case === 'viewer_blocks_owner' ? $owner : $viewer,
            ]);
        } elseif ($case === 'private') {
            DB::table('accounts')->where('account_identifier', $owner)->update(['visibility' => 'private']);
        } elseif ($case === 'unavailable_routine') {
            DB::table('routines')->where('routine_identifier', $routine)->update(['available' => false]);
        } elseif ($case === 'unavailable_owner') {
            DB::table('accounts')->where('account_identifier', $owner)->update(['available' => false]);
        } elseif ($case === 'inactive_owner') {
            DB::table('accounts')->where('account_identifier', $owner)->update(['status' => 'temporarily_banned']);
        } elseif ($case === 'missing') {
            $routine = '33333333-3333-4333-8333-333333333333';
        }

        $before = DB::table('routine_execution_drafts')->get()->all();
        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertStatus($status);
        self::assertEquals($before, DB::table('routine_execution_drafts')->get()->all());
        $this->assertDatabaseCount('routine_execution_draft_actions', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $viewer, 'experience' => 0]);
    }

    public static function inaccessibleRoutineCases(): array
    {
        return [
            'viewer blocks owner' => ['viewer_blocks_owner', 403],
            'owner blocks viewer' => ['owner_blocks_viewer', 403],
            'private' => ['private', 403],
            'unavailable routine' => ['unavailable_routine', 403],
            'unavailable owner' => ['unavailable_owner', 403],
            'inactive owner' => ['inactive_owner', 403],
            'missing' => ['missing', 404],
        ];
    }

    public function test_private_routine_is_executable_by_owner_and_follower(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $follower = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'private'],
            ['account_identifier' => $follower, 'account_name' => 'Follower', 'email_address' => 'follower@example.com', 'visibility' => 'public'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $owner]);
        DB::table('follows')->insert(['following_account_identifier' => $follower, 'followed_account_identifier' => $owner]);

        $this->withSession(['account_identifier' => $owner])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
        $this->withSession(['account_identifier' => $follower])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
        DB::table('follows')->where('following_account_identifier', $follower)->delete();
        $this->withSession(['account_identifier' => $follower])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertForbidden();
        $this->assertDatabaseCount('routine_execution_drafts', 2);
    }

    public function test_unauthenticated_or_inactive_account_cannot_create_a_draft(): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $owner = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com', 'status' => 'temporarily_banned'],
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'status' => 'active'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $owner]);

        $this->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertUnauthorized();
        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertUnauthorized();
        $this->assertDatabaseCount('routine_execution_drafts', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $viewer, 'experience' => 0]);
    }

    #[DataProvider('accountEligibilityChanges')]
    public function test_account_changes_after_authentication_follow_existing_repository_behavior(?array $change, int $status, int $draftCount): void
    {
        $viewer = '11111111-1111-4111-8111-111111111111';
        $owner = '44444444-4444-4444-8444-444444444444';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com'],
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $owner]);

        $repository = \Mockery::mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('findWithLock')->once()->andReturnUsing(function ($identifier) use ($viewer, $change) {
            if ($change === null) {
                DB::table('accounts')->where('account_identifier', $viewer)->delete();
            } else {
                DB::table('accounts')->where('account_identifier', $viewer)->update($change);
            }

            return $this->app->make(AccountRepository::class)->findWithLock($identifier);
        });
        $this->app->instance(AccountRepositoryInterface::class, $repository);

        $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertStatus($status);
        $this->assertDatabaseCount('routine_execution_drafts', $draftCount);
        $this->assertDatabaseCount('routine_executions', 0);
    }

    public static function accountEligibilityChanges(): array
    {
        return [
            'stopped' => [['status' => 'temporarily_banned'], 201, 1],
            'permanently stopped' => [['status' => 'permanently_banned'], 201, 1],
            'unavailable' => [['available' => false], 201, 1],
            'deleted' => [null, 422, 0],
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
