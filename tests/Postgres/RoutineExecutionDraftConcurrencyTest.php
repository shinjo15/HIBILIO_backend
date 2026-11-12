<?php

declare(strict_types=1);

namespace Tests\Postgres;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\Support\PostgresDatabaseGuard;
use Tests\TestCase;

final class RoutineExecutionDraftConcurrencyTest extends TestCase
{
    #[DataProvider('outcomes')]
    public function test_concurrent_creation_serializes_and_survives_rollback(string $mode, string $secondOutcome): void
    {
        if (! defined('HIBILIO_POSTGRES_REGRESSION')) {
            throw new \RuntimeException('Run only with the guarded PostgreSQL test bootstrap.');
        }
        PostgresDatabaseGuard::assertConnection($this->app);
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'User', 'email_address' => 'concurrent@example.com']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'routine_name' => 'Routine', 'account_identifier' => $account]);
        $input = new InputStream;
        $first = new Process([PHP_BINARY, base_path('tests/Support/routine-execution-draft-concurrency-worker.php'), $mode], base_path(), null, $input, 30);
        $second = new Process([PHP_BINARY, base_path('tests/Support/routine-execution-draft-concurrency-worker.php'), 'contend'], base_path(), null, null, 30);
        try {
            $first->start();
            self::assertTrue($first->waitUntil(fn () => str_contains($first->getOutput(), "INSERTED\n")), $first->getErrorOutput());
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
            self::assertTrue($blocked, 'Second transaction did not block: '.$second->getErrorOutput());
            $input->write("continue\n");
            $input->close();
            self::assertSame(0, $first->wait(), $first->getErrorOutput());
            self::assertSame(0, $second->wait(), $second->getErrorOutput());
            self::assertStringContainsString($mode === 'rollback' ? "ROLLED_BACK\n" : "CREATED\n", $first->getOutput());
            self::assertStringContainsString($secondOutcome."\n", $second->getOutput());
            self::assertSame(1, DB::table('routine_execution_drafts')->count());
            self::assertSame(0, DB::table('routine_executions')->count());
            self::assertSame(0, DB::table('posts')->count());
            self::assertSame(0, (int) DB::table('accounts')->where('account_identifier', $account)->value('experience'));
        } finally {
            $first->stop();
            $second->stop();
            $this->artisan('migrate:fresh', ['--force' => true]);
        }
    }

    public static function outcomes(): array
    {
        return ['commit then no-op' => ['hold', 'NOOP'], 'rollback then create' => ['rollback', 'CREATED']];
    }
}
