<?php

declare(strict_types=1);

namespace Tests\Postgres;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\Support\PostgresDatabaseGuard;
use Tests\TestCase;

final class RoutineExecutionConcurrencyTest extends TestCase
{
    #[DataProvider('outcomes')]
    public function test_same_account_draft_completions_serialize_and_preserve_committed_experience(string $mode, int $expected): void
    {
        if (! defined('HIBILIO_POSTGRES_REGRESSION')) {
            throw new \RuntimeException('Run only with the guarded PostgreSQL test bootstrap.');
        }
        PostgresDatabaseGuard::assertConnection($this->app);
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $account = '11111111-1111-4111-8111-111111111111';
        $routines = ['22222222-2222-4222-8222-222222222222', '22222222-2222-4222-8222-222222222223'];
        $actions = ['33333333-3333-4333-8333-333333333333', '33333333-3333-4333-8333-333333333334'];
        $drafts = ['44444444-4444-4444-8444-444444444444', '44444444-4444-4444-8444-444444444445'];
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'concurrent@example.com']);
        foreach ($routines as $index => $routine) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $account]);
            DB::table('routine_actions')->insert(['routine_action_identifier' => $actions[$index], 'routine_identifier' => $routine, 'action_name' => 'Action', 'position' => 0]);
            DB::table('routine_execution_drafts')->insert(['routine_execution_identifier' => $drafts[$index], 'executor_account_identifier' => $account, 'routine_identifier' => $routine, 'executed_at' => '2026-01-01 12:00:00', 'routine_execution_memo' => 'memo']);
            DB::table('routine_execution_action_drafts')->insert(['routine_execution_identifier' => $drafts[$index], 'routine_action_identifier' => $actions[$index]]);
        }

        $input = new InputStream;
        $first = new Process([PHP_BINARY, base_path('tests/Support/routine-execution-concurrency-worker.php'), $mode], base_path(), null, $input, 30);
        $second = new Process([PHP_BINARY, base_path('tests/Support/routine-execution-concurrency-worker.php'), 'contend'], base_path(), null, null, 30);
        try {
            $first->start();
            self::assertTrue($first->waitUntil(fn () => str_contains($first->getOutput(), "LOCKED\n")), $first->getErrorOutput());
            self::assertSame(1, preg_match('/PID (\d+)/', $first->getOutput(), $firstMatch));
            $second->start();
            self::assertTrue($second->waitUntil(fn () => preg_match('/PID (\d+)/', $second->getOutput()) === 1), $second->getErrorOutput());
            preg_match('/PID (\d+)/', $second->getOutput(), $secondMatch);
            $firstPid = (int) $firstMatch[1];
            $secondPid = (int) $secondMatch[1];
            self::assertNotSame($firstPid, $secondPid);

            $deadline = microtime(true) + 15;
            $blocked = false;
            do {
                $blocked = (bool) DB::selectOne('select ? = any(pg_blocking_pids(?)) as blocked', [$firstPid, $secondPid])->blocked;
                if ($blocked) {
                    break;
                }
                usleep(20000);
            } while ($second->isRunning() && microtime(true) < $deadline);
            self::assertTrue($blocked, 'Second use case did not wait for the first PostgreSQL transaction: '.$second->getErrorOutput());
            self::assertSame(0, (int) DB::table('accounts')->where('account_identifier', $account)->value('experience'));

            $input->write("continue\n");
            $input->close();
            self::assertSame(0, $first->wait(), $first->getErrorOutput());
            self::assertSame(0, $second->wait(), $second->getErrorOutput());
            self::assertStringContainsString($mode === 'rollback' ? "ROLLED_BACK\n" : "COMMITTED\n", $first->getOutput());
            self::assertStringContainsString("COMMITTED\n", $second->getOutput());
            self::assertSame($expected * 100, (int) DB::table('accounts')->where('account_identifier', $account)->value('experience'));
            self::assertSame($expected, DB::table('routine_executions')->where('executor_account_identifier', $account)->count());
            self::assertSame($expected, DB::table('routine_execution_actions')->count());
            self::assertSame($expected, DB::table('posts')->count());
            $this->assertDatabaseCount('routine_execution_drafts', 2 - $expected);
            $this->assertDatabaseCount('routine_execution_action_drafts', 2 - $expected);
            foreach ($drafts as $index => $draft) {
                $committed = $index === 1 || $mode !== 'rollback';
                if ($committed) {
                    $this->assertDatabaseHas('routine_executions', ['routine_execution_identifier' => $draft, 'routine_identifier' => $routines[$index], 'routine_execution_memo' => 'memo']);
                    $this->assertDatabaseHas('routine_execution_actions', ['routine_execution_identifier' => $draft, 'routine_action_identifier' => $actions[$index]]);
                    $this->assertDatabaseHas('posts', ['routine_execution_identifier' => $draft, 'routine_identifier' => $routines[$index]]);
                    $this->assertDatabaseMissing('routine_execution_drafts', ['routine_execution_identifier' => $draft]);
                    $this->assertDatabaseMissing('routine_execution_action_drafts', ['routine_execution_identifier' => $draft]);
                } else {
                    $this->assertDatabaseMissing('routine_executions', ['routine_execution_identifier' => $draft]);
                    $this->assertDatabaseMissing('routine_execution_actions', ['routine_execution_identifier' => $draft]);
                    $this->assertDatabaseMissing('posts', ['routine_execution_identifier' => $draft]);
                    $this->assertDatabaseHas('routine_execution_drafts', ['routine_execution_identifier' => $draft, 'routine_execution_memo' => 'memo']);
                    $this->assertDatabaseHas('routine_execution_action_drafts', ['routine_execution_identifier' => $draft, 'routine_action_identifier' => $actions[$index]]);
                }
            }
        } finally {
            $first->stop();
            $second->stop();
            $this->artisan('migrate:fresh', ['--force' => true]);
        }
    }

    public static function outcomes(): array
    {
        return ['both commit' => ['hold', 2], 'first rolls back' => ['rollback', 1]];
    }
}
