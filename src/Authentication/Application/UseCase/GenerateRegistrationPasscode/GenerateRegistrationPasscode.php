<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Authentication\Application\Service\LoginPasscodeGeneratorServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeHashServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeMailServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeStateServiceInterface;
use Src\Authentication\Domain\Exception\RegistrationEmailAddressAlreadyRegisteredException;
use Src\Authentication\Domain\Factory\RegistrationPasscodeChallengeFactoryInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscode;

final readonly class GenerateRegistrationPasscode implements GenerateRegistrationPasscodeInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private RegistrationPasscodeChallengeFactoryInterface $challengeFactory,
        private LoginPasscodeGeneratorServiceInterface $generator,
        private LoginPasscodeHashServiceInterface $hashService,
        private RegistrationPasscodeStateServiceInterface $stateService,
        private RegistrationPasscodeMailServiceInterface $mailService,
    ) {}

    public function execute(GenerateRegistrationPasscodeInputPort $input): GenerateRegistrationPasscodeOutputPort
    {
        if ($this->accountRepository->findByEmailAddress($input->emailAddress()) !== null) {
            throw new RegistrationEmailAddressAlreadyRegisteredException;
        }

        $passcode = new LoginPasscode($this->generator->generate());
        $challenge = $this->challengeFactory->create($input->emailAddress(), $this->hashService->hash($passcode));
        $this->stateService->register($challenge);
        $this->mailService->send($challenge->emailAddress(), $passcode);

        return new GenerateRegistrationPasscodeOutput($challenge->identifier()->value());
    }
}
