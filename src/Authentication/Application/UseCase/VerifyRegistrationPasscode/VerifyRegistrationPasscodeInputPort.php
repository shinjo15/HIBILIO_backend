<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;

interface VerifyRegistrationPasscodeInputPort
{
    public function challengeIdentifier(): RegistrationPasscodeChallengeIdentifier;

    public function passcode(): LoginPasscode;
}
