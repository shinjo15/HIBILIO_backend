<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use DateTimeImmutable;

interface PersistentLoginClockServiceInterface
{
    public function now(): DateTimeImmutable;
}
