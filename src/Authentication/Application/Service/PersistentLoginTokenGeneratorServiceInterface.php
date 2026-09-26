<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

interface PersistentLoginTokenGeneratorServiceInterface
{
    public function selector(): PersistentLoginSelector;

    public function validator(): string;
}
