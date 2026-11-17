<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Factory;

use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface LoginPasscodeChallengeFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): LoginPasscodeChallenge;
}
