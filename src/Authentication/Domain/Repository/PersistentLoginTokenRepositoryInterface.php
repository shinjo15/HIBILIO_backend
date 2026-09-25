<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Repository;

use Src\Authentication\Domain\Entity\PersistentLoginToken;

interface PersistentLoginTokenRepositoryInterface
{
    public function find(string $selector): ?PersistentLoginToken;

    public function save(PersistentLoginToken $token): void;

    public function delete(string $selector): void;
}
