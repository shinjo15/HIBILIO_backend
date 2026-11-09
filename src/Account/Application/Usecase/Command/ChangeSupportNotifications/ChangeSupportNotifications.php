<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeSupportNotifications;

use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class ChangeSupportNotifications implements ChangeSupportNotificationsInterface
{
    public function __construct(private AccountRepositoryInterface $accountRepository, private TransactionManagerInterface $transactionManager) {}

    public function execute(ChangeSupportNotificationsInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException;
        }
        $account->changeSupportNotifications($input->enabled());
        $this->transactionManager->transaction(fn (): mixed => $this->accountRepository->save($account));
    }
}
