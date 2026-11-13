<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CompleteRoutineExecutionDraft;

use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Exception\AccountNotFoundException;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Factory\PostFactoryInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;

final readonly class CompleteRoutineExecutionDraft implements CompleteRoutineExecutionDraftInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private RoutineExecutionDraftRepositoryInterface $draftRepository,
        private RoutineExecutionActionDraftRepositoryInterface $actionDraftRepository,
        private RoutineExecutionRepositoryInterface $routineExecutionRepository,
        private RoutineExecutionActionRepositoryInterface $routineExecutionActionRepository,
        private AccountRepositoryInterface $accountRepository,
        private PostFactoryInterface $postFactory,
        private PostRepositoryInterface $postRepository,
    ) {}

    public function execute(CompleteRoutineExecutionDraftInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $draft = $this->draftRepository->find($input->executorAccountIdentifier(), $input->routineIdentifier()) ?? throw new RoutineExecutionDraftNotFoundException;
            $actions = $this->actionDraftRepository->findByDraft($draft);
            $execution = new RoutineExecution($draft->routineExecutionIdentifier(), $draft->executorAccountIdentifier(), $draft->routineIdentifier(), $draft->executedAt(), $draft->routineExecutionMemo());
            $account = $this->accountRepository->find($execution->executorAccountIdentifier()) ?? throw new AccountNotFoundException;
            $this->routineExecutionRepository->save($execution);
            foreach ($actions as $action) {
                $this->routineExecutionActionRepository->save($action);
            }
            $this->postRepository->save($this->postFactory->createActionPost($execution->routineIdentifier(), $execution->routineExecutionIdentifier()));
            $account->recordRoutineExecution();
            $this->accountRepository->save($account);
            $this->draftRepository->delete($draft);
        });
    }
}
