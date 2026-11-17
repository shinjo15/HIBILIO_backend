<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Factory;

use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\Factory\LoginPasscodeChallengeFactoryInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LoginPasscodeChallengeFactory implements LoginPasscodeChallengeFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountIdentifier $accountIdentifier, EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): LoginPasscodeChallenge
    {
        return new LoginPasscodeChallenge(
            new LoginPasscodeChallengeIdentifier($this->uuidService->generate()),
            $accountIdentifier,
            $emailAddress,
            $passcodeHash,
        );
    }
}
