<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Repository;

use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

interface PersistentLoginTokenRepositoryInterface
{
    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken;

    public function save(PersistentLoginToken $token): void;

    public function deleteBySelector(PersistentLoginSelector $selector): void;
}
