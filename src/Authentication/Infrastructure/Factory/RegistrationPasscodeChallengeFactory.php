<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Factory;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Domain\Entity\RegistrationPasscodeChallenge;
use Src\Authentication\Domain\Factory\RegistrationPasscodeChallengeFactoryInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;
use Src\Shared\Application\Service\UuidServiceInterface;

final readonly class RegistrationPasscodeChallengeFactory implements RegistrationPasscodeChallengeFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): RegistrationPasscodeChallenge
    {
        return new RegistrationPasscodeChallenge(
            new RegistrationPasscodeChallengeIdentifier($this->uuidService->generate()),
            $emailAddress,
            $passcodeHash,
        );
    }
}
