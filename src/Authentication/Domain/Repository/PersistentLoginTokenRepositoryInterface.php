<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Repository;

use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

interface PersistentLoginTokenRepositoryInterface
{
    public function find(PersistentLoginSelector $selector): ?PersistentLoginToken;

    public function save(PersistentLoginToken $token): void;

    public function delete(PersistentLoginSelector $selector): void;
}
