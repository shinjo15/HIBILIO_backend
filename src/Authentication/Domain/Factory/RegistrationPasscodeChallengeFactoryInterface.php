<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Factory;

use Src\Authentication\Domain\Entity\RegistrationPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\EmailAddress;

interface RegistrationPasscodeChallengeFactoryInterface
{
    public function create(EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): RegistrationPasscodeChallenge;
}
