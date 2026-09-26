<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use DateTimeImmutable;
use Src\Authentication\Application\Service\PersistentLoginClockServiceInterface;

final readonly class SystemPersistentLoginClockService implements PersistentLoginClockServiceInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
