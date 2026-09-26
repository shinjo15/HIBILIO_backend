<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service;

use Illuminate\Support\Facades\Hash;
use Src\Authentication\Application\Service\PersistentLoginTokenHashServiceInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;

final readonly class LaravelPersistentLoginTokenHashService implements PersistentLoginTokenHashServiceInterface
{
    public function hash(string $validator): PersistentLoginValidatorHash
    {
        return new PersistentLoginValidatorHash(Hash::make($validator));
    }

    public function matches(string $validator, PersistentLoginValidatorHash $hash): bool
    {
        return Hash::check($validator, $hash->value());
    }
}
