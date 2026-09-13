<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\CompleteSocialLogin;

use Illuminate\Database\QueryException;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Domain\Entity\SocialLoginConnection;
use Src\Authentication\Domain\Repository\SocialLoginConnectionRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CompleteSocialLogin implements CompleteSocialLoginInterface
{
    public function __construct(
        private SocialLoginServiceInterface $socialLoginService,
        private SocialLoginConnectionRepositoryInterface $connectionRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private TransactionManagerInterface $transactionManager,
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

        try {
            $accountIdentifier = $this->transactionManager->transaction(function () use ($input, $profile): ?AccountIdentifier {
                $connected = $this->connectionRepository->findAccountIdentifier(
                    $input->provider(),
                    $profile['provider_user_identifier'],
                );
                if ($connected !== null) {
                    return $connected;
                }

                $emailAddress = new EmailAddress($profile['email_address']);
                $account = $this->accountRepository->findByEmailAddress($emailAddress);
                if ($account !== null) {
                    return $this->saveConnection($account->accountIdentifier(), $input, $profile['provider_user_identifier']);
                }

                $accountName = $profile['account_name'] ?? null;
                if (! is_string($accountName) || trim($accountName) === '') {
                    return null;
                }

                $account = $this->accountFactory->create(
                    new AccountName($accountName),
                    null,
                    $emailAddress,
                    [],
                    new FavoriteTagIdentifiers([]),
                );
                $this->accountRepository->save($account);

                return $this->saveConnection($account->accountIdentifier(), $input, $profile['provider_user_identifier']);
            });
        } catch (QueryException) {
            // A concurrent callback may have won the unique email/provider race.
            $accountIdentifier = $this->connectionRepository->findAccountIdentifier(
                $input->provider(),
                $profile['provider_user_identifier'],
            ) ?? $this->accountRepository->findByEmailAddress(new EmailAddress($profile['email_address']))?->accountIdentifier();
        }

        return $accountIdentifier instanceof AccountIdentifier
            ? CompleteSocialLoginOutput::authenticated($accountIdentifier)
            : CompleteSocialLoginOutput::rejected();
    }

    /** @param array{provider_user_identifier: string, email_address: string, account_name: ?string} $profile */
    private function saveConnection(AccountIdentifier $accountIdentifier, CompleteSocialLoginInputPort $input, string $providerUserIdentifier): ?AccountIdentifier
    {
        $connection = new SocialLoginConnection($accountIdentifier, $input->provider(), $providerUserIdentifier);
        if ($this->connectionRepository->save($connection)) {
            return $accountIdentifier;
        }

        $connected = $this->connectionRepository->findAccountIdentifier($input->provider(), $providerUserIdentifier);

        return $connected?->value() === $accountIdentifier->value() ? $accountIdentifier : null;
    }
}
