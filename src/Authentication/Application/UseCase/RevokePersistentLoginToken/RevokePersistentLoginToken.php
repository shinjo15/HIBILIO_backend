<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Application\Service\HashServiceInterface;

final readonly class RevokePersistentLoginToken implements RevokePersistentLoginTokenInterface
{
    public function __construct(private PersistentLoginTokenRepositoryInterface $tokenRepository, private HashServiceInterface $hashService) {}

    public function execute(RevokePersistentLoginTokenInputPort $input): RevokePersistentLoginTokenOutputPort
    {
        $token = $this->tokenRepository->findBySelector($input->selector());
        if ($token === null || ! $this->hashService->matches($input->rawValidator(), $token->validatorHash()->value())) {
            return new RevokePersistentLoginTokenOutput(false);
        }

        return new RevokePersistentLoginTokenOutput($this->tokenRepository->deleteBySelector($token->selector()));
    }
}
