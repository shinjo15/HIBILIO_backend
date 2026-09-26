<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Src\Authentication\Application\Service\LoginPasscodeHashServiceInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Application\Service\HashServiceInterface;

final class LoginPasscodeHashService implements LoginPasscodeHashServiceInterface
{
    public function __construct(private HashServiceInterface $hashService) {}

    public function hash(LoginPasscode $passcode): LoginPasscodeHash
    {
        return new LoginPasscodeHash($this->hashService->hash($passcode->value()));
    }

    public function matches(LoginPasscode $passcode, LoginPasscodeHash $passcodeHash): bool
    {
        return $this->hashService->matches($passcode->value(), $passcodeHash->value());
    }
}
