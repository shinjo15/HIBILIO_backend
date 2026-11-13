<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use App\Http\Actions\RoutineExecution\GetMyRoutineExecutionDraftsAction;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInput;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Tests\TestCase;

final class GetMyRoutineExecutionDraftsActionTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const ROUTINE2 = '44444444-4444-4444-8444-444444444444';

    private const ROUTINE3 = '99999999-9999-4999-8999-999999999999';

    private const DRAFT = '55555555-5555-4555-8555-555555555555';

    private const DRAFT2 = '66666666-6666-4666-8666-666666666666';

    private const OTHER_DRAFT = '77777777-7777-4777-8777-777777777777';

    private const EMPTY_DRAFT = '88888888-8888-4888-8888-888888888888';

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
            ['account_identifier' => self::USER, 'account_name' => 'User', 'email_address' => 'user@example.com', 'visibility' => 'public', 'available' => true],
            ['account_identifier' => self::OTHER, 'account_name' => 'Other', 'email_address' => 'other@example.com', 'visibility' => 'private', 'available' => false],
        ]);
        DB::table('routines')->insert([
            ['routine_identifier' => self::ROUTINE, 'routine_name' => 'One', 'account_identifier' => self::OTHER, 'available' => false],
            ['routine_identifier' => self::ROUTINE2, 'routine_name' => 'Two', 'account_identifier' => self::OTHER, 'available' => true],
            ['routine_identifier' => self::ROUTINE3, 'routine_name' => 'Three', 'account_identifier' => self::OTHER, 'available' => true],
        ]);
        DB::table('routine_actions')->insert([
            ['routine_action_identifier' => self::A, 'routine_identifier' => self::ROUTINE, 'action_name' => 'A', 'position' => 0],
            ['routine_action_identifier' => self::B, 'routine_identifier' => self::ROUTINE, 'action_name' => 'B', 'position' => 1],
            ['routine_action_identifier' => self::C, 'routine_identifier' => self::ROUTINE2, 'action_name' => 'C', 'position' => 0],
        ]);
    }

    public function test_returns_only_session_accounts_drafts_with_saved_actions(): void
    {
        DB::table('routine_execution_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => 'memo'],
            ['routine_execution_identifier' => self::DRAFT2, 'executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE2, 'executed_at' => '2026-01-02 12:00:00', 'routine_execution_memo' => null],
            ['routine_execution_identifier' => self::OTHER_DRAFT, 'executor_account_identifier' => self::OTHER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-03 12:00:00', 'routine_execution_memo' => 'private'],
            ['routine_execution_identifier' => self::EMPTY_DRAFT, 'executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE3, 'executed_at' => '2026-01-04 12:00:00', 'routine_execution_memo' => null],
        ]);
        DB::table('routine_execution_action_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::B],
            ['routine_execution_identifier' => self::DRAFT, 'routine_action_identifier' => self::A],
            ['routine_execution_identifier' => self::DRAFT2, 'routine_action_identifier' => self::C],
            ['routine_execution_identifier' => self::OTHER_DRAFT, 'routine_action_identifier' => self::A],
        ]);

        $this->withSession(['account_identifier' => self::USER])->getJson('/api/my/routine-execution-drafts?account_identifier='.self::OTHER)
            ->assertOk()->assertExactJson(['items' => [
                ['routine_execution_identifier' => self::DRAFT, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01T12:00:00.000000+00:00', 'routine_execution_memo' => 'memo', 'executed_routine_action_identifiers' => [self::A, self::B]],
                ['routine_execution_identifier' => self::DRAFT2, 'routine_identifier' => self::ROUTINE2, 'executed_at' => '2026-01-02T12:00:00.000000+00:00', 'routine_execution_memo' => null, 'executed_routine_action_identifiers' => [self::C]],
                ['routine_execution_identifier' => self::EMPTY_DRAFT, 'routine_identifier' => self::ROUTINE3, 'executed_at' => '2026-01-04T12:00:00.000000+00:00', 'routine_execution_memo' => null, 'executed_routine_action_identifiers' => []],
            ], 'total' => 3]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/my/routine-execution-drafts')->assertUnauthorized();
    }

    public function test_authentication_failure_is_not_converted_to_unauthorized(): void
    {
        $auth = $this->createStub(AuthServiceInterface::class);
        $auth->method('accountIdentifier')->willThrowException(new \RuntimeException('database failure'));
        $action = new GetMyRoutineExecutionDraftsAction($this->createStub(GetMyRoutineExecutionDraftsInterface::class), $auth);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('database failure');
        $action();
    }

    public function test_returns_empty_collection_when_only_another_account_has_a_draft(): void
    {
        DB::table('routine_execution_drafts')->insert([
            'routine_execution_identifier' => self::OTHER_DRAFT, 'executor_account_identifier' => self::OTHER,
            'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-03 12:00:00',
        ]);

        $this->withSession(['account_identifier' => self::USER])->getJson('/api/my/routine-execution-drafts')
            ->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
    }

    public function test_query_count_does_not_grow_with_number_of_drafts(): void
    {
        DB::table('routine_execution_drafts')->insert([
            ['routine_execution_identifier' => self::DRAFT, 'executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE, 'executed_at' => '2026-01-01 12:00:00'],
            ['routine_execution_identifier' => self::DRAFT2, 'executor_account_identifier' => self::USER, 'routine_identifier' => self::ROUTINE2, 'executed_at' => '2026-01-02 12:00:00'],
        ]);

        $queries = [];
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        Storage::swap(\Mockery::mock(FilesystemManager::class));
        $result = $this->app->make(GetMyRoutineExecutionDraftsInterface::class)->execute(new GetMyRoutineExecutionDraftsInput(self::USER));
        self::assertSame(2, $result->total());
        self::assertCount(1, $queries);
        self::assertStringContainsString('left join', strtolower($queries[0]));
        self::assertStringContainsString('routine_execution_action_drafts', strtolower($queries[0]));
    }
}
