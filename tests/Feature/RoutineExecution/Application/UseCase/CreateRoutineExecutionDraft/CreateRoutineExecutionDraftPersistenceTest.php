<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\TestCase;

final class CreateRoutineExecutionDraftPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshots_all_fields_and_keeps_each_creators_copy_after_source_changes(): void
    {
        $author = '11111111-1111-4111-8111-111111111111';
        $owner = '22222222-2222-4222-8222-222222222222';
        $other = '33333333-3333-4333-8333-333333333333';
        $routine = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $parent = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $first = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        $second = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
        $tag = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
        $otherTag = 'ffffffff-ffff-4fff-8fff-ffffffffffff';
        foreach ([$author, $owner, $other] as $i => $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => 'name', 'email_address' => "{$i}@example.com", 'status' => 'active', 'visibility' => 'public', 'available' => true]);
        }
        DB::table('routines')->insert(['routine_identifier' => $parent, 'account_identifier' => $author, 'routine_name' => 'parent']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'parent_routine_identifier' => $parent, 'account_identifier' => $author, 'routine_name' => 'original', 'routine_memo' => 'memo', 'routine_execution_minutes' => 20, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'available' => false, 'created_at' => '2020-01-01 01:02:03', 'updated_at' => '2020-02-02 02:03:04']);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $first, 'routine_identifier' => $routine, 'action_name' => 'first', 'action_memo' => 'first memo', 'action_minutes' => 5, 'position' => 0, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $second, 'parent_routine_action_identifier' => $first, 'routine_identifier' => $routine, 'action_name' => 'second', 'action_memo' => 'second memo', 'action_minutes' => 6, 'position' => 1, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=2', 'available' => false, 'created_at' => '2020-01-02 03:04:05', 'updated_at' => '2020-02-03 04:05:06']);
        DB::table('tags')->insert(['tag_identifier' => $tag, 'tag_name' => 'tag']);
        DB::table('tags')->insert(['tag_identifier' => $otherTag, 'tag_name' => 'disabled', 'available' => false]);
        DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => $tag]);
        DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => $otherTag, 'available' => false]);
        $usecase = app(CreateRoutineExecutionDraftInterface::class);
        $create = fn (string $id) => $usecase->execute(new CreateRoutineExecutionDraftInput(new RoutineIdentifier($routine), new AccountIdentifier($id)));
        self::assertNull($create($owner));
        DB::table('routines')->where('routine_identifier', $routine)->update(['routine_name' => 'changed', 'routine_memo' => null, 'video_url' => null]);
        DB::table('routine_actions')->where('routine_action_identifier', $second)->update(['action_name' => 'changed action', 'position' => 4]);
        DB::table('routine_tags')->where('routine_identifier', $routine)->delete();
        $create($other);

        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'parent_routine_identifier' => $parent, 'account_identifier' => $author, 'routine_name' => 'original', 'routine_memo' => 'memo', 'routine_execution_minutes' => 20, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'available' => false, 'source_created_at' => '2020-01-01 01:02:03', 'source_updated_at' => '2020-02-02 02:03:04']);
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => $other, 'routine_identifier' => $routine, 'routine_name' => 'changed']);
        $this->assertDatabaseHas('routine_execution_draft_actions', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'routine_action_identifier' => $first, 'parent_routine_action_identifier' => null, 'action_name' => 'first', 'action_memo' => 'first memo', 'action_minutes' => 5, 'position' => 0, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->assertDatabaseHas('routine_execution_draft_actions', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'routine_action_identifier' => $second, 'parent_routine_action_identifier' => $first, 'action_name' => 'second', 'action_memo' => 'second memo', 'action_minutes' => 6, 'position' => 1, 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=2', 'available' => false, 'source_created_at' => '2020-01-02 03:04:05', 'source_updated_at' => '2020-02-03 04:05:06']);
        $this->assertDatabaseHas('routine_execution_draft_actions', ['draft_creator_account_identifier' => $other, 'routine_identifier' => $routine, 'routine_action_identifier' => $second, 'action_name' => 'changed action', 'position' => 4]);
        $this->assertDatabaseHas('routine_execution_draft_tags', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'tag_identifier' => $tag]);
        $this->assertDatabaseHas('routine_execution_draft_tags', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'tag_identifier' => $otherTag]);
        $this->assertDatabaseCount('routine_execution_draft_tags', 2);
        DB::table('routines')->where('routine_identifier', $routine)->delete();
        DB::table('tags')->where('tag_identifier', $tag)->delete();
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'routine_name' => 'original']);
        $this->assertDatabaseCount('routine_execution_draft_actions', 4);
        $this->assertDatabaseHas('routine_execution_draft_actions', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'routine_action_identifier' => $second, 'parent_routine_action_identifier' => $first, 'position' => 1]);
        $this->assertDatabaseHas('routine_execution_draft_tags', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine, 'tag_identifier' => $tag]);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $owner, 'experience' => 0]);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $other, 'experience' => 0]);

        DB::table('accounts')->where('account_identifier', $owner)->delete();
        $this->assertDatabaseMissing('routine_execution_drafts', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine]);
        $this->assertDatabaseMissing('routine_execution_draft_actions', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine]);
        $this->assertDatabaseMissing('routine_execution_draft_tags', ['draft_creator_account_identifier' => $owner, 'routine_identifier' => $routine]);
        $this->assertDatabaseHas('routine_execution_drafts', ['draft_creator_account_identifier' => $other, 'routine_identifier' => $routine]);
        $this->assertDatabaseHas('routine_execution_draft_actions', ['draft_creator_account_identifier' => $other, 'routine_identifier' => $routine]);
    }

    public function test_failed_action_insert_rolls_back_the_entire_draft(): void
    {
        $owner = '22222222-2222-4222-8222-222222222222';
        $routine = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'name', 'email_address' => 'rollback@example.com', 'status' => 'active', 'visibility' => 'public', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'routine']);
        DB::table('routine_actions')->insert(['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine_identifier' => $routine, 'action_name' => 'first', 'position' => 0]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'routine_identifier' => $routine, 'action_name' => 'second', 'position' => 1]);
        DB::unprepared("CREATE TRIGGER fail_draft_action BEFORE INSERT ON routine_execution_draft_actions WHEN NEW.action_name = 'second' BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
        $inserted = 0;
        DB::listen(static function ($query) use (&$inserted): void {
            if (str_starts_with($query->sql, 'insert into "routine_execution_draft_actions"')) {
                $inserted++;
            }
        });

        try {
            app(CreateRoutineExecutionDraftInterface::class)->execute(new CreateRoutineExecutionDraftInput(new RoutineIdentifier($routine), new AccountIdentifier($owner)));
            self::fail('The insert must fail.');
        } catch (QueryException $exception) {
            self::assertStringContainsString('forced failure', $exception->getMessage());
        }

        self::assertSame(1, $inserted);
        $this->assertDatabaseCount('routine_execution_drafts', 0);
        $this->assertDatabaseCount('routine_execution_draft_actions', 0);
        $this->assertDatabaseCount('routine_execution_draft_tags', 0);
    }

    public function test_routine_without_actions_is_rejected_by_existing_domain_invariant(): void
    {
        $owner = '22222222-2222-4222-8222-222222222222';
        $routine = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'name', 'email_address' => 'empty@example.com', 'status' => 'active', 'visibility' => 'public', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'empty']);

        $this->expectException(\InvalidArgumentException::class);
        app(CreateRoutineExecutionDraftInterface::class)->execute(new CreateRoutineExecutionDraftInput(new RoutineIdentifier($routine), new AccountIdentifier($owner)));
    }
}
