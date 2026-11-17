<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Entity;

use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LoginPasscodeChallenge
{
    public const EXPIRATION_SECONDS = 600;

    public const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(
        private LoginPasscodeChallengeIdentifier $identifier,
        private AccountIdentifier $accountIdentifier,
        private EmailAddress $emailAddress,
        private LoginPasscodeHash $passcodeHash,
    ) {}

    public function identifier(): LoginPasscodeChallengeIdentifier
    {
        return $this->identifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
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
