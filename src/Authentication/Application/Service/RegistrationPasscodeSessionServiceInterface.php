<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Account\Domain\ValueObject\EmailAddress;

interface RegistrationPasscodeSessionServiceInterface
{
    public function challengeIdentifier(): string;

    public function setChallengeIdentifier(string $challengeIdentifier): void;

    public function clearChallengeIdentifier(): void;

    public function verifiedEmailAddress(): EmailAddress;

    public function setVerifiedEmailAddress(EmailAddress $emailAddress): void;

    public function clearVerifiedEmailAddress(): void;
}
