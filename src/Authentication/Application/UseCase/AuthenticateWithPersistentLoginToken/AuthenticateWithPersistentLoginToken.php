<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken;

use DateTimeImmutable;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Application\Service\HashServiceInterface;

final readonly class AuthenticateWithPersistentLoginToken implements AuthenticateWithPersistentLoginTokenInterface
{
    public function __construct(private PersistentLoginTokenRepositoryInterface $tokenRepository, private HashServiceInterface $hashService, private AccountRepositoryInterface $accountRepository) {}

    public function execute(AuthenticateWithPersistentLoginTokenInputPort $input): AuthenticateWithPersistentLoginTokenOutputPort
    {
        $token = $this->tokenRepository->findBySelector($input->selector());
        if ($token === null || $token->isExpired(new DateTimeImmutable) || ! $this->hashService->matches($input->rawValidator(), $token->validatorHash()->value())) {
            return new AuthenticateWithPersistentLoginTokenOutput(null);
        }

        $account = $this->accountRepository->find($token->accountIdentifier());
        if ($account === null || $account->status() !== AccountStatus::ACTIVE) {
            return new AuthenticateWithPersistentLoginTokenOutput(null);
        }

        return new AuthenticateWithPersistentLoginTokenOutput($account->accountIdentifier());
    }
}
