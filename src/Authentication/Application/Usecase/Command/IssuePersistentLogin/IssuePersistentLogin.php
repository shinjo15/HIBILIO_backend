<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\IssuePersistentLogin;

use Src\Authentication\Application\Service\PersistentLoginClockServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenGeneratorServiceInterface;
use Src\Authentication\Application\Service\PersistentLoginTokenHashServiceInterface;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class IssuePersistentLogin implements IssuePersistentLoginInterface
{
    private const EXPIRATION_MINUTES = PersistentLoginExpiresAt::EXPIRATION_DAYS * 24 * 60;

    public function __construct(
        private PersistentLoginTokenRepositoryInterface $tokens,
        private PersistentLoginTokenGeneratorServiceInterface $generator,
        private PersistentLoginTokenHashServiceInterface $hashService,
        private PersistentLoginClockServiceInterface $clock,
        private PersistentLoginCookieServiceInterface $cookie,
        private AuthServiceInterface $authService,
    ) {}

    public function execute(IssuePersistentLoginInputPort $input): IssuePersistentLoginOutputPort
    {
        $selector = $this->generator->selector();
        $validator = $this->generator->validator();
        $expiresAt = PersistentLoginExpiresAt::fromIssuedAt($this->clock->now());
        $this->tokens->save(new PersistentLoginToken(
            $selector,
            $input->accountIdentifier(),
            $this->hashService->hash($validator),
            $expiresAt,
        ));
        $this->authService->login($input->accountIdentifier());
        $this->cookie->queue($selector->value().'.'.$validator, self::EXPIRATION_MINUTES);

        return new IssuePersistentLoginOutput;
    }
}
