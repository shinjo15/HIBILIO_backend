<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GeneratePersistentLoginToken;

use DateTimeImmutable;

interface GeneratePersistentLoginTokenOutputPort
{
    public function selector(): string;

    public function rawValidator(): string;

    public function expiresAt(): DateTimeImmutable;
}
