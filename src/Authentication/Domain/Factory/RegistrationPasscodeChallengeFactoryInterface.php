<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Factory;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\Entity\RegistrationPasscodeChallenge;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;

interface RegistrationPasscodeChallengeFactoryInterface
{
    public function create(EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): RegistrationPasscodeChallenge;
}
