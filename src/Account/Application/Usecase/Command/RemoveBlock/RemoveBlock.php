<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveBlock;

use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class RemoveBlock implements RemoveBlockInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private BlockRepositoryInterface $blockRepository) {}

    public function execute(RemoveBlockInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $this->blockRepository->delete($input->blockingAccountIdentifier(), $input->blockedAccountIdentifier());
        });
    }
}
