<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Src\Authentication\Application\Service\PersistentLoginTokenGeneratorServiceInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

final readonly class RandomPersistentLoginTokenGeneratorService implements PersistentLoginTokenGeneratorServiceInterface
{
    public function selector(): PersistentLoginSelector
    {
        return new PersistentLoginSelector(bin2hex(random_bytes(32)));
    }

    public function validator(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
