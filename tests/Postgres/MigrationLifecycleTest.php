<?php

declare(strict_types=1);

namespace Tests\Postgres;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MigrationLifecycleTest extends TestCase
{
    public function test_all_migrations_can_be_applied_rolled_back_and_reapplied_on_postgres_18(): void
    {
        self::assertSame('pgsql', DB::connection()->getDriverName());
        self::assertSame('hibilio_postgres_test', DB::connection()->getDatabaseName());
        self::assertStringStartsWith('18.', DB::selectOne('show server_version')->server_version);

        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $migrations = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
        self::assertCount(count(glob(database_path('migrations/*.php'))), $migrations);

        $this->artisan('migrate:rollback', ['--force' => true])->assertExitCode(0);
        self::assertFalse(Schema::hasTable('routine_actions'));
        self::assertSame(0, DB::table('migrations')->count());

        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        self::assertSame($migrations, DB::table('migrations')->orderBy('migration')->pluck('migration')->all());
    }
}
