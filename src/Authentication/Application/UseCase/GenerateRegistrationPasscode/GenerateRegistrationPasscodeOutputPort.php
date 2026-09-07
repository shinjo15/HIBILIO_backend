<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

interface GenerateRegistrationPasscodeOutputPort
{
    public function challengeIdentifier(): ?string;
}
