<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

interface PersistentLoginCookieServiceInterface
{
    public function value(): ?string;

    public function queue(string $value, int $minutes): void;

    public function clear(): void;
}
