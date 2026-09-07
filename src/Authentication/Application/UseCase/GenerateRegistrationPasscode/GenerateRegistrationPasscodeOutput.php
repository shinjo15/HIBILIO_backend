<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

final readonly class GenerateRegistrationPasscodeOutput
{
    public function __construct(private ?string $challengeIdentifier) {}

    public function challengeIdentifier(): ?string
    {
        return $this->challengeIdentifier;
    }
}
