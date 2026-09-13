<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Repository;

use Src\Authentication\Domain\Entity\SocialLoginConnection;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface SocialLoginConnectionRepositoryInterface
{
    public function findAccountIdentifier(SocialLoginProvider $provider, string $providerUserIdentifier): ?AccountIdentifier;

    public function save(SocialLoginConnection $connection): bool;
}
