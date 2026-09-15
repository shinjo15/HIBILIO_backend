<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

use Src\Account\Application\Service\AccountRegistrationMailServiceInterface;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Domain\Exception\DuplicateEmailAddressException;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Authentication\Domain\Entity\SocialLoginConnection;
use Src\Authentication\Domain\Repository\SocialLoginConnectionRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateAccount implements CreateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private AccountRegistrationMailServiceInterface $accountRegistrationMailService,
        private TransactionManagerInterface $transactionManager,
        private StorageServiceInterface $storageService,
        private ?SocialLoginConnectionRepositoryInterface $socialLoginConnectionRepository = null,
    ) {}

    public function execute(CreateAccountInputPort $input): CreateAccountOutputPort
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

            if ($input->pendingSocialRegistration() !== null) {
                if ($this->socialLoginConnectionRepository === null) {
                    throw new \LogicException('ソーシャルログイン接続リポジトリが設定されていません。');
                }

                $registration = $input->pendingSocialRegistration();
                $saved = $this->socialLoginConnectionRepository->save(new SocialLoginConnection(
                    $account->accountIdentifier(),
                    $registration->provider(),
                    $registration->providerUserIdentifier(),
                ));
                if (! $saved) {
                    throw new \RuntimeException('ソーシャルログイン接続を保存できませんでした。');
                }
            }
        });

        $this->accountRegistrationMailService->send($account->emailAddress(), $account->accountName());

        return new CreateAccountOutput($account->accountIdentifier());
    }
}
