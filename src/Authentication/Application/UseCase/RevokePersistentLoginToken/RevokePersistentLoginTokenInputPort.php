<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

interface RevokePersistentLoginTokenInputPort
{
    public function selector(): PersistentLoginSelector;

    public function rawValidator(): string;
}
