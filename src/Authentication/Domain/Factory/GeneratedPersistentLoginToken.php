<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Factory;

use Src\Authentication\Domain\Entity\PersistentLoginToken;

final readonly class GeneratedPersistentLoginToken
{
    public function __construct(
        private PersistentLoginToken $persistentLoginToken,
        private string $rawValidator,
    ) {}

    public function persistentLoginToken(): PersistentLoginToken
    {
        return $this->persistentLoginToken;
    }

    public function rawValidator(): string
    {
        return $this->rawValidator;
    }
}
