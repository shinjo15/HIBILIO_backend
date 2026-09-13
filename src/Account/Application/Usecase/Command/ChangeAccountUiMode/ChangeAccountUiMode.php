<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountUiMode;

use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class ChangeAccountUiMode implements ChangeAccountUiModeInterface
{
    public function __construct(private AccountRepositoryInterface $accountRepository, private TransactionManagerInterface $transactionManager) {}

    public function execute(ChangeAccountUiModeInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException;
        }
        $account->changeUiMode($input->uiMode());
        $this->transactionManager->transaction(fn (): mixed => $this->accountRepository->save($account));
    }
}
