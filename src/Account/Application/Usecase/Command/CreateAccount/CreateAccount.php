<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

use Src\Account\Application\Service\AccountRegistrationMailServiceInterface;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Domain\Exception\DuplicateEmailAddressException;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateAccount implements CreateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private AccountRegistrationMailServiceInterface $accountRegistrationMailService,
        private TransactionManagerInterface $transactionManager,
        private StorageServiceInterface $storageService,
    ) {}

    public function execute(CreateAccountInputPort $input): void
    {
        if ($this->accountRepository->findByEmailAddress($input->emailAddress()) !== null) {
            throw new DuplicateEmailAddressException;
        }

        $account = $this->accountFactory->create(
            $input->accountName(), $input->accountBio(), $input->emailAddress(), $input->socialLinks(), $input->favoriteTagIdentifiers(),
        );
        $this->transactionManager->transaction(function () use ($account, $input): void {
            $this->accountRepository->save($account);

            if ($input->icon() !== null) {
                $this->storageService->uploadIcon(
                    $account->accountIdentifier(),
                    $input->icon(),
                );
            }

            if ($input->header() !== null) {
                $this->storageService->uploadHeader(
                    $account->accountIdentifier(),
                    $input->header(),
                );
            }
        });

        $this->accountRegistrationMailService->send($account->emailAddress(), $account->accountName());
    }
}
