<?php

declare(strict_types=1);

namespace Src\Account\Application\UseCase\UpdateAccountProfile;

use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class UpdateAccountProfile implements UpdateAccountProfileInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private StorageServiceInterface $storageService,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function execute(UpdateAccountProfileInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException;
        }

        $account->updateProfile(
            $input->accountName(),
            $input->hasAccountBio(),
            $input->accountBio(),
            $input->socialLinks(),
            $input->favoriteTagIdentifiers(),
        );

        $this->transactionManager->transaction(function () use ($account, $input): void {
            $this->accountRepository->save($account);

            if ($input->deleteIcon()) {
                $this->storageService->deleteIcon($account->accountIdentifier());
            } elseif ($input->icon() !== null) {
                $this->storageService->uploadIcon($account->accountIdentifier(), $input->icon());
            }

            if ($input->deleteHeader()) {
                $this->storageService->deleteHeader($account->accountIdentifier());
            } elseif ($input->header() !== null) {
                $this->storageService->uploadHeader($account->accountIdentifier(), $input->header());
            }
        });
    }
}
