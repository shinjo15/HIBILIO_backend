<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GeneratePersistentLoginToken;

use DateTimeImmutable;
use Src\Authentication\Domain\Factory\PersistentLoginTokenFactoryInterface;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;

final readonly class GeneratePersistentLoginToken implements GeneratePersistentLoginTokenInterface
{
    public function __construct(
        private PersistentLoginTokenFactoryInterface $tokenFactory,
        private PersistentLoginTokenRepositoryInterface $tokenRepository,
    ) {}

    public function execute(GeneratePersistentLoginTokenInputPort $input): GeneratePersistentLoginTokenOutputPort
    {
        $generatedToken = $this->tokenFactory->create($input->accountIdentifier(), new DateTimeImmutable);
        $persistentLoginToken = $generatedToken->persistentLoginToken();
        $this->tokenRepository->save($persistentLoginToken);

        return new GeneratePersistentLoginTokenOutput(
            $persistentLoginToken->selector()->value(),
            $generatedToken->rawValidator(),
            $persistentLoginToken->expiresAt()->value(),
        );
    }
}
