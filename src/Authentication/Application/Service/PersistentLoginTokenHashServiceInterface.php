<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;

interface PersistentLoginTokenHashServiceInterface
{
    public function hash(string $validator): PersistentLoginValidatorHash;
}
