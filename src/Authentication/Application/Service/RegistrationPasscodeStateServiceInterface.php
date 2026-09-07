<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\Entity\RegistrationPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;

interface RegistrationPasscodeStateServiceInterface
{
    public function register(RegistrationPasscodeChallenge $challenge): void;

    public function find(RegistrationPasscodeChallengeIdentifier $challengeIdentifier): ?RegistrationPasscodeChallenge;

    public function recordFailedAttempt(RegistrationPasscodeChallengeIdentifier $challengeIdentifier): ?int;

    public function delete(RegistrationPasscodeChallengeIdentifier $challengeIdentifier): bool;
}
