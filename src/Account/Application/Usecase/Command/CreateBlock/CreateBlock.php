<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateBlock;

use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateBlockException;
use Src\Account\Domain\Factory\BlockFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateBlock implements CreateBlockInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AccountRepositoryInterface $accountRepository,
        private BlockRepositoryInterface $blockRepository,
        private BlockFactoryInterface $blockFactory,
    ) {}

    public function execute(CreateBlockInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            if ($this->accountRepository->find($input->blockedAccountIdentifier()) === null) {
                throw new AccountNotFoundException;
            }

            if ($this->blockRepository->find(
                $input->blockingAccountIdentifier(),
                $input->blockedAccountIdentifier(),
            ) !== null) {
                throw new DuplicateBlockException;
            }

            $this->blockRepository->save($this->blockFactory->create(
                blockingAccountIdentifier: $input->blockingAccountIdentifier(),
                blockedAccountIdentifier: $input->blockedAccountIdentifier(),
            ));
        });
    }
}
