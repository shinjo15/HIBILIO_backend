<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Factory\GeneratedPersistentLoginToken;
use Src\Authentication\Domain\Factory\PersistentLoginTokenFactoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersistentLoginTokenFactory implements PersistentLoginTokenFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, DateTimeImmutable $issuedAt): GeneratedPersistentLoginToken
    {
        $rawValidator = bin2hex(random_bytes(32));

        return new GeneratedPersistentLoginToken(
            new PersistentLoginToken(
                new PersistentLoginSelector(bin2hex(random_bytes(16))),
                $accountIdentifier,
                new PersistentLoginValidatorHash(Hash::make($rawValidator)),
                PersistentLoginExpiresAt::create($issuedAt),
            ),
            $rawValidator,
        );
    }
}
