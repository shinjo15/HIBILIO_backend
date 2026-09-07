<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;

final readonly class VerifyRegistrationPasscodeInput
{
    public function __construct(
        private RegistrationPasscodeChallengeIdentifier $challengeIdentifier,
        private LoginPasscode $passcode,
    ) {}

    public function challengeIdentifier(): RegistrationPasscodeChallengeIdentifier
    {
        return $this->challengeIdentifier;
    }

    public function passcode(): LoginPasscode
    {
        return $this->passcode;
    }
}
