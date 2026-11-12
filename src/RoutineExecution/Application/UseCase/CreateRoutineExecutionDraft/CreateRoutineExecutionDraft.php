<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\RoutineExecution\Domain\Exception\AccountNotAuthenticatedException;
use Src\RoutineExecution\Domain\Exception\RoutineNotExecutableException;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundForDraftException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\ExecutableRoutineRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class CreateRoutineExecutionDraft
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AccountRepositoryInterface $accounts,
        private ExecutableRoutineRepositoryInterface $routines,
        private RoutineExecutionDraftRepositoryInterface $drafts,
        private RoutineExecutionFactoryInterface $factory,
    ) {}

    public function execute(AccountIdentifier $account, RoutineIdentifier $routine): bool
    {
        return $this->transactionManager->transaction(function () use ($account, $routine): bool {
            $lockedAccount = $this->accounts->findForUpdate($account);
            if ($lockedAccount === null || ! $lockedAccount->canAuthenticate()) {
                throw new AccountNotAuthenticatedException;
            }
            $executableRoutine = $this->routines->find($routine, $account) ?? throw new RoutineNotFoundForDraftException;
            if (! $executableRoutine->canBeExecuted()) {
                throw new RoutineNotExecutableException;
            }
            if ($this->drafts->exists($account, $routine)) {
                return false;
            }
            $this->drafts->save($this->factory->create($account, $routine, null));

            return true;
        });
    }
}
