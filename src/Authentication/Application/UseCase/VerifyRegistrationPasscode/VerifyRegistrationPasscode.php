<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

use Src\Authentication\Application\Service\LoginPasscodeHashServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeStateServiceInterface;

final readonly class VerifyRegistrationPasscode implements VerifyRegistrationPasscodeInterface
{
    public function __construct(
        private RegistrationPasscodeStateServiceInterface $stateService,
        private LoginPasscodeHashServiceInterface $hashService,
    ) {}

    public function execute(VerifyRegistrationPasscodeInputPort $input): VerifyRegistrationPasscodeOutputPort
    {
        $challenge = $this->stateService->find($input->challengeIdentifier());
        if ($challenge === null) {
            return VerifyRegistrationPasscodeOutput::rejected();
        }

        if (! $this->hashService->matches($input->passcode(), $challenge->passcodeHash())) {
            $this->stateService->recordFailedAttempt($input->challengeIdentifier());

            return VerifyRegistrationPasscodeOutput::rejected();
        }

        if (! $this->stateService->delete($input->challengeIdentifier())) {
            return VerifyRegistrationPasscodeOutput::rejected();
        }

        return VerifyRegistrationPasscodeOutput::verified($challenge->emailAddress());
    }
}
