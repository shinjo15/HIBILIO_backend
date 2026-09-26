<?php

declare(strict_types=1);

namespace Src\Shared\Application\Service;

interface HashServiceInterface
{
    public function hash(string $value): string;

    public function matches(string $value, string $hash): bool;
}
