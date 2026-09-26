<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

interface PersistentLoginCookieServiceInterface
{
    public function queue(string $value, int $minutes): void;
}
