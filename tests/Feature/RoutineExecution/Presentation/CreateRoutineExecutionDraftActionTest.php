<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Application\Service\UuidServiceInterface;
use Tests\TestCase;

final class CreateRoutineExecutionDraftActionTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '11111111-1111-4111-8111-111111111111';

    private const AUTHOR = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const OTHER = '44444444-4444-4444-8444-444444444444';

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
            ['account_identifier' => self::USER, 'account_name' => 'User', 'email_address' => 'user@example.com'],
            ['account_identifier' => self::AUTHOR, 'account_name' => 'Author', 'email_address' => 'author@example.com'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => 'Routine', 'account_identifier' => self::AUTHOR]);
    }

    public function test_creates_draft_for_session_account_on_another_accounts_public_routine(): void
    {
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', [
            'routine_identifier' => self::ROUTINE, 'executor_account_identifier' => self::AUTHOR,
        ])->assertCreated()->assertNoContent(201);
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE, 'routine_execution_memo' => null]);
        $this->assertDatabaseCount('routine_execution_action_drafts', 0);
        $this->assertDatabaseCount('routine_executions', 0);
        $this->assertDatabaseCount('routine_execution_actions', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseHas('accounts', ['account_identifier' => self::USER, 'experience' => 0]);
    }

    public function test_creates_draft_when_image_storage_is_unavailable(): void
    {
        $images = $this->createStub(AccountImageUrlServiceInterface::class);
        $images->method('iconImageUrl')->willThrowException(new \RuntimeException('Image storage unavailable'));
        $this->app->instance(AccountImageUrlServiceInterface::class, $images);

        $this->withSession(['account_identifier' => self::USER])
            ->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])
            ->assertCreated();
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE]);
    }

    public function test_requires_authentication_and_valid_identifier(): void
    {
        $this->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertUnauthorized();
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', [])->assertUnprocessable()->assertJsonValidationErrors('routine_identifier');
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => 'invalid'])->assertUnprocessable();
        $this->assertDatabaseCount('routine_execution_drafts', 0);
    }

    public function test_missing_routine_causes_a_foreign_key_server_error(): void
    {
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::OTHER])->assertStatus(500);
        $this->assertDatabaseCount('routine_execution_drafts', 0);
    }

    public function test_private_routine_without_a_follow_can_be_created_by_direct_identifier(): void
    {
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => self::USER])->getJson('/api/routines/'.self::ROUTINE)->assertNotFound();
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertCreated();
        $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE]);
    }

    public function test_private_routine_is_visible_to_its_owner_and_follower(): void
    {
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['visibility' => 'private']);
        DB::table('follows')->insert(['following_account_identifier' => self::USER, 'followed_account_identifier' => self::AUTHOR]);
        foreach ([self::USER, self::AUTHOR] as $viewer) {
            $this->withSession(['account_identifier' => $viewer])->getJson('/api/routines/'.self::ROUTINE)->assertOk();
            $this->withSession(['account_identifier' => $viewer])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertCreated();
            $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => $viewer, 'routine_identifier' => self::ROUTINE]);
        }
    }

    public function test_blocks_in_either_direction_do_not_prevent_creation_by_direct_identifier(): void
    {
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['visibility' => 'private']);
        DB::table('follows')->insert(['following_account_identifier' => self::USER, 'followed_account_identifier' => self::AUTHOR]);
        foreach ([[self::USER, self::AUTHOR], [self::AUTHOR, self::USER]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => self::USER])->getJson('/api/routines/'.self::ROUTINE)->assertNotFound();
            $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertCreated();
            $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE]);
            DB::table('routine_execution_drafts')->delete();
            DB::table('blocks')->delete();
        }
    }

    public function test_unavailable_routine_or_author_and_inactive_author_do_not_prevent_creation(): void
    {
        foreach ([['routines', 'available', false], ['accounts', 'available', false], ['accounts', 'status', 'temporarily_banned'], ['accounts', 'status', 'permanently_banned']] as [$table, $column, $value]) {
            $identifier = $table === 'routines' ? 'routine_identifier' : 'account_identifier';
            $target = $table === 'routines' ? self::ROUTINE : self::AUTHOR;
            DB::table($table)->where($identifier, $target)->update([$column => $value]);
            $this->withSession(['account_identifier' => self::USER])->getJson('/api/routines/'.self::ROUTINE)->assertNotFound();
            $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => self::ROUTINE])->assertCreated();
            $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE]);
            DB::table('routine_execution_drafts')->delete();
            DB::table($table)->where($identifier, $target)->update([$column => $column === 'status' ? 'active' : true]);
        }
    }

    public function test_inaccessible_existing_draft_still_returns_conflict(): void
    {
        $payload = ['routine_identifier' => self::ROUTINE];
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', $payload)->assertCreated();
        $original = DB::table('routine_execution_drafts')->first();
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', $payload)->assertStatus(409);
        $this->assertDatabaseCount('routine_execution_drafts', 1);
        self::assertEquals($original, DB::table('routine_execution_drafts')->first());
    }

    public function test_duplicate_is_conflict_without_changing_existing_draft(): void
    {
        $payload = ['routine_identifier' => self::ROUTINE];
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', $payload)->assertCreated();
        DB::table('routine_execution_drafts')->update(['routine_execution_memo' => '既存']);
        $original = DB::table('routine_execution_drafts')->first();
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', $payload)->assertStatus(409);
        $this->assertDatabaseCount('routine_execution_drafts', 1);
        self::assertEquals($original, DB::table('routine_execution_drafts')->first());
        $this->withSession(['account_identifier' => self::AUTHOR])->postJson('/api/routine-execution-drafts', $payload)->assertCreated();
        $this->assertDatabaseCount('routine_execution_drafts', 2);
    }

    public function test_different_routines_can_have_drafts_at_the_same_time(): void
    {
        DB::table('routines')->insert(['routine_identifier' => self::OTHER, 'routine_name' => 'Own', 'account_identifier' => self::USER]);
        foreach ([self::ROUTINE, self::OTHER] as $routine) {
            $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', ['routine_identifier' => $routine])->assertCreated();
            $this->assertDatabaseHas('routine_execution_drafts', ['executor_account_identifier' => self::USER, 'routine_identifier' => $routine]);
        }
        $this->assertDatabaseCount('routine_execution_drafts', 2);
    }

    public function test_unrelated_unique_and_foreign_key_errors_are_server_errors(): void
    {
        $uuid = $this->createStub(UuidServiceInterface::class);
        $uuid->method('generate')->willReturn(self::OTHER);
        $this->app->instance(UuidServiceInterface::class, $uuid);
        $payload = ['routine_identifier' => self::ROUTINE];
        $this->withSession(['account_identifier' => self::USER])->postJson('/api/routine-execution-drafts', $payload)->assertCreated();
        $this->withSession(['account_identifier' => self::AUTHOR])->postJson('/api/routine-execution-drafts', $payload)->assertStatus(500);
        $this->assertDatabaseCount('routine_execution_drafts', 1);
        $otherUuid = $this->createStub(UuidServiceInterface::class);
        $otherUuid->method('generate')->willReturn('66666666-6666-4666-8666-666666666666');
        $this->app->instance(UuidServiceInterface::class, $otherUuid);
        $auth = $this->createStub(AuthServiceInterface::class);
        $auth->method('accountIdentifier')->willReturn('55555555-5555-4555-8555-555555555555');
        $this->app->instance(AuthServiceInterface::class, $auth);
        $this->postJson('/api/routine-execution-drafts', $payload)->assertStatus(500);
        $this->assertDatabaseCount('routine_execution_drafts', 1);
    }
}
