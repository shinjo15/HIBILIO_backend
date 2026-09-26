<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\IssuePersistentLogin\IssuePersistentLoginInput;
use Src\Authentication\Application\Usecase\Command\IssuePersistentLogin\IssuePersistentLoginInterface;
use Src\Authentication\Domain\Entity\SocialLoginConnection;
use Src\Authentication\Domain\Repository\SocialLoginConnectionRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CompleteSocialLogin implements CompleteSocialLoginInterface
{
    public function __construct(
        private SocialLoginServiceInterface $socialLoginService,
        private SocialLoginConnectionRepositoryInterface $connectionRepository,
        private AccountRepositoryInterface $accountRepository,
        private TransactionManagerInterface $transactionManager,
        private IssuePersistentLoginInterface $issuePersistentLogin,
    ) {}

    public function execute(CompleteSocialLoginInputPort $input): CompleteSocialLoginOutputPort
    {
        $profile = $this->socialLoginService->authenticate(
            $input->provider(),
            $input->authorizationCode(),
            $input->state(),
            $input->browserSessionIdentifier(),
        );
        if ($profile === null) {
            return CompleteSocialLoginOutput::rejected();
        }

        $providerUserIdentifier = $profile['provider_user_identifier'];
        $connectedAccountIdentifier = $this->connectionRepository->findAccountIdentifier(
            $input->provider(),
            $providerUserIdentifier,
        );
        if ($connectedAccountIdentifier !== null) {
            $this->issuePersistentLogin->execute(new IssuePersistentLoginInput($connectedAccountIdentifier));

            return CompleteSocialLoginOutput::authenticated($connectedAccountIdentifier);
        }

        $emailAddress = new EmailAddress($profile['email_address']);
        $account = $this->accountRepository->findByEmailAddress($emailAddress);
        if ($account === null) {
            return CompleteSocialLoginOutput::pending(new PendingSocialRegistration(
                $input->provider(),
                $providerUserIdentifier,
                $emailAddress,
            ));
        }

        $accountIdentifier = $account->accountIdentifier();
        $linked = $this->transactionManager->transaction(function () use ($accountIdentifier, $input, $providerUserIdentifier): bool {
            if ($this->connectionRepository->save(new SocialLoginConnection(
                $accountIdentifier,
                $input->provider(),
                $providerUserIdentifier,
            ))) {
                return true;
            }

            return $this->connectionRepository->findAccountIdentifier(
                $input->provider(),
                $providerUserIdentifier,
            )?->value() === $accountIdentifier->value();
        });

        if (! $linked) {
            return CompleteSocialLoginOutput::rejected();
        }

        $this->issuePersistentLogin->execute(new IssuePersistentLoginInput($accountIdentifier));

        return CompleteSocialLoginOutput::authenticated($accountIdentifier);
    }
}
