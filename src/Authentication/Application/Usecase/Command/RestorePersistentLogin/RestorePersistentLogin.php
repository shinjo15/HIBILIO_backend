<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\RestorePersistentLogin;

use Src\Authentication\Application\Service\PersistentLoginClockServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenGeneratorServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenHashServiceInterface;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInput;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInterface;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RestorePersistentLogin implements RestorePersistentLoginInterface
{
    public function __construct(
        private PersistentLoginTokenRepositoryInterface $tokens,
        private PersistentLoginTokenGeneratorServiceInterface $generator,
        private PersistentLoginTokenHashServiceInterface $hashService,
        private PersistentLoginClockServiceInterface $clock,
        private PersistentLoginCookieServiceInterface $cookie,
        private GetAuthenticatedAccountStateInterface $authenticatedAccountState,
        private AuthServiceInterface $authService,
    ) {}

    public function execute(RestorePersistentLoginInputPort $input): RestorePersistentLoginOutputPort
    {
        $value = $this->cookie->value();
        if ($value === null) {
            return new RestorePersistentLoginOutput(null);
        }
        $parts = explode('.', $value, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            $this->cookie->clear();

            return new RestorePersistentLoginOutput(null);
        }
        $selector = new PersistentLoginSelector($parts[0]);
        $token = $this->tokens->find($selector);
        if ($token === null || $token->isExpired($this->clock->now()) || ! $this->hashService->matches($parts[1], $token->validatorHash()) || ! $this->authenticatedAccountState->execute(new GetAuthenticatedAccountStateInput($token->accountIdentifier()))->isAuthenticated()) {
            $this->tokens->delete($selector);
            $this->cookie->clear();

            return new RestorePersistentLoginOutput(null);
        }
        $this->tokens->delete($selector);
        $newSelector = $this->generator->selector();
        $validator = $this->generator->validator();
        $this->tokens->save(new PersistentLoginToken($newSelector, $token->accountIdentifier(), $this->hashService->hash($validator), $token->expiresAt()));
        $remainingMinutes = max(0, intdiv($token->expiresAt()->value()->getTimestamp() - $this->clock->now()->getTimestamp() + 59, 60));
        $this->cookie->queue($newSelector->value().'.'.$validator, $remainingMinutes);
        $this->authService->login($token->accountIdentifier());

        return new RestorePersistentLoginOutput($token->accountIdentifier());
    }
}
