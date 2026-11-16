<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\UseCase\CompleteRoutineExecutionDraft\CompleteRoutineExecutionDraftInput;
use Src\RoutineExecution\Application\UseCase\CompleteRoutineExecutionDraft\CompleteRoutineExecutionDraftInterface;
use Src\RoutineExecution\Domain\Entity\Account;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\Support\PostgresDatabaseGuard;

require __DIR__.'/postgres-bootstrap.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
PostgresDatabaseGuard::assertConnection($app);
$repository = $app->make(AccountRepositoryInterface::class);
$mode = $argv[1] ?? '';
if (! in_array($mode, ['hold', 'rollback', 'contend'], true)) {
    throw new InvalidArgumentException('Invalid worker mode');
}
$app->instance(AccountRepositoryInterface::class, new class($repository, $mode) implements AccountRepositoryInterface
{
    public function __construct(private AccountRepositoryInterface $inner, private string $mode) {}

    public function find(AccountIdentifier $id): ?Account
    {
        $account = $this->inner->find($id);
        if ($this->mode !== 'contend') {
            fwrite(STDOUT, "LOCKED\n");
            fflush(STDOUT);
            if (trim((string) fgets(STDIN)) !== 'continue') {
                throw new RuntimeException('Worker was not released');
            }
        }

        return $account;
    }

    public function findForUpdate(AccountIdentifier $id): ?Account
    {
        return $this->inner->findForUpdate($id);
    }

    public function save(Account $account): void
    {
        $this->inner->save($account);
        if ($this->mode === 'rollback') {
            throw new RuntimeException('injected concurrent rollback');
        }
    }
});

fwrite(STDOUT, 'PID '.DB::selectOne('select pg_backend_pid() as pid')->pid."\n");
fflush(STDOUT);
try {
    $app->make(CompleteRoutineExecutionDraftInterface::class)->execute(new CompleteRoutineExecutionDraftInput(
        new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
        new RoutineIdentifier($mode === 'contend' ? '22222222-2222-4222-8222-222222222223' : '22222222-2222-4222-8222-222222222222'),
    ));
    fwrite(STDOUT, "COMMITTED\n");
} catch (Throwable $exception) {
    if ($mode !== 'rollback' || $exception::class !== RuntimeException::class || $exception->getMessage() !== 'injected concurrent rollback') {
        throw $exception;
    }
    fwrite(STDOUT, "ROLLED_BACK\n");
}
