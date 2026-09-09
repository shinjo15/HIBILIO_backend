<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountStatus;

use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class ChangeAccountStatus implements ChangeAccountStatusInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function execute(ChangeAccountStatusInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException;
        }

        match ($input->status()) {
            AccountStatus::ACTIVE => $account->active(),
            AccountStatus::TEMPORARILY_BANNED => $account->temporarilyBan(),
            AccountStatus::PERMANENTLY_BANNED => $account->permanentlyBan(),
        };

        $this->transactionManager->transaction(function () use ($account): void {
            $this->accountRepository->save($account);
        });
    }
}
