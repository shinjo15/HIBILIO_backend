<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Service;

use Illuminate\Support\Facades\Hash;
use Src\Shared\Application\Service\HashServiceInterface;

final class LaravelHashService implements HashServiceInterface
{
    public function hash(string $value): string
    {
        return Hash::make($value);
    }

    public function matches(string $value, string $hash): bool
    {
        return Hash::check($value, $hash);
    }
}
