<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

use DateTimeImmutable;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenOutput;
use Src\Authentication\Domain\Factory\PersistentLoginTokenFactoryInterface;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Application\Service\HashServiceInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class AuthenticateWithPersistentLoginToken implements AuthenticateWithPersistentLoginTokenInterface
{
    public function __construct(private PersistentLoginTokenRepositoryInterface $tokenRepository, private HashServiceInterface $hashService, private AccountRepositoryInterface $accountRepository, private PersistentLoginTokenFactoryInterface $tokenFactory, private TransactionManagerInterface $transactionManager) {}

    public function execute(AuthenticateWithPersistentLoginTokenInputPort $input): AuthenticateWithPersistentLoginTokenOutputPort
    {
        $token = $this->tokenRepository->findBySelector($input->selector());
        if ($token === null) {
            return new AuthenticateWithPersistentLoginTokenOutput(null, null);
        }
        if ($token->isExpired(new DateTimeImmutable) || ! $this->hashService->matches($input->rawValidator(), $token->validatorHash()->value())) {
            $this->tokenRepository->deleteBySelector($token->selector());

            return new AuthenticateWithPersistentLoginTokenOutput(null, null);
        }

        $account = $this->accountRepository->find($token->accountIdentifier());
        if ($account === null || ! $account->isAvailable() || $account->status() !== AccountStatus::ACTIVE) {
            $this->tokenRepository->deleteBySelector($token->selector());

            return new AuthenticateWithPersistentLoginTokenOutput(null, null);
        }

        $rotated = $this->transactionManager->transaction(function () use ($token) {
            if (! $this->tokenRepository->deleteBySelector($token->selector())) {
                return null;
            }
            $rotated = $this->tokenFactory->rotate($token);
            $this->tokenRepository->save($rotated->persistentLoginToken());

            return $rotated;
        });
        if ($rotated === null) {
            return new AuthenticateWithPersistentLoginTokenOutput(null, null);
        }

        return new AuthenticateWithPersistentLoginTokenOutput($account->accountIdentifier(), new GeneratePersistentLoginTokenOutput($rotated->persistentLoginToken()->selector()->value(), $rotated->rawValidator(), $rotated->persistentLoginToken()->expiresAt()->value()));
    }
}
