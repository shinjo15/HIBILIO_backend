<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInput;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\RoutineExecution\Domain\Exception\AccountNotFoundException;
use Src\RoutineExecution\Domain\Exception\RoutineNotExecutableException;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundForDraftException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateRoutineExecutionDraft implements CreateRoutineExecutionDraftInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AccountRepositoryInterface $accounts,

        private RoutineRepositoryInterface $routines,
        private GetRoutineDetailsInterface $routineDetails,
        private RoutineExecutionDraftRepositoryInterface $drafts,
        private RoutineExecutionFactoryInterface $factory,
    ) {}

    public function execute(CreateRoutineExecutionDraftInputPort $input): CreateRoutineExecutionDraftOutputPort
    {
        return $this->transactionManager->transaction(function () use ($input): CreateRoutineExecutionDraftOutputPort {
            $this->accounts->findWithLock($input->executorAccountIdentifier()) ?? throw new AccountNotFoundException;
            if (! $this->routines->exists($input->routineIdentifier())) {
                throw new RoutineNotFoundForDraftException;
            }
            $details = $this->routineDetails->execute(new GetRoutineDetailsInput($input->routineIdentifier()->value(), $input->executorAccountIdentifier()->value()));
            if ($details->routineDetails() === null) {
                throw new RoutineNotExecutableException;
            }
            if ($this->drafts->exists($input->executorAccountIdentifier(), $input->routineIdentifier())) {
                return new CreateRoutineExecutionDraftOutput(false);
            }
            $this->drafts->save($this->factory->create($input->executorAccountIdentifier(), $input->routineIdentifier(), null));

            return new CreateRoutineExecutionDraftOutput(true);
        });
    }
}
