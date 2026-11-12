<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraft;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\Support\PostgresDatabaseGuard;

require __DIR__.'/postgres-bootstrap.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
PostgresDatabaseGuard::assertConnection($app);
$mode = $argv[1] ?? '';
if (! in_array($mode, ['hold', 'rollback', 'contend'], true)) {
    throw new InvalidArgumentException('Invalid worker mode');
}
$repository = $app->make(RoutineExecutionDraftRepositoryInterface::class);
$app->instance(RoutineExecutionDraftRepositoryInterface::class, new class($repository, $mode) implements RoutineExecutionDraftRepositoryInterface
{
    public function __construct(private RoutineExecutionDraftRepositoryInterface $inner, private string $mode) {}

    public function exists(AccountIdentifier $accountIdentifier, RoutineIdentifier $routineIdentifier): bool
    {
        return $this->inner->exists($accountIdentifier, $routineIdentifier);
    }

    public function save(RoutineExecution $routineExecution): void
    {
        $this->inner->save($routineExecution);
        if ($this->mode !== 'contend') {
            fwrite(STDOUT, "INSERTED\n");
            fflush(STDOUT);
            if (trim((string) fgets(STDIN)) !== 'continue') {
                throw new RuntimeException('Worker was not released');
            }
            if ($this->mode === 'rollback') {
                throw new RuntimeException('injected rollback');
            }
        }
    }
});
fwrite(STDOUT, 'PID '.DB::selectOne('select pg_backend_pid() as pid')->pid."\n");
fflush(STDOUT);
try {
    $created = $app->make(CreateRoutineExecutionDraft::class)->execute(
        new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
        new RoutineIdentifier('22222222-2222-4222-8222-222222222222'),
    );
    fwrite(STDOUT, $created ? "CREATED\n" : "NOOP\n");
} catch (Throwable $exception) {
    if ($mode !== 'rollback' || $exception::class !== RuntimeException::class || $exception->getMessage() !== 'injected rollback') {
        throw $exception;
    }
    fwrite(STDOUT, "ROLLED_BACK\n");
}
