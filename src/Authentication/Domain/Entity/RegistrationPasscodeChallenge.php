<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entity;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;

final readonly class RegistrationPasscodeChallenge
{
    public const EXPIRATION_SECONDS = 600;

    public const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(
        private RegistrationPasscodeChallengeIdentifier $identifier,
        private EmailAddress $emailAddress,
        private LoginPasscodeHash $passcodeHash,
    ) {}

    public function identifier(): RegistrationPasscodeChallengeIdentifier
    {
        return $this->identifier;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function passcodeHash(): LoginPasscodeHash
    {
        return $this->passcodeHash;
    }
}
