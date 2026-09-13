<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\PendingSocialRegistration;

interface PendingSocialRegistrationSessionServiceInterface
{
    public function pending(): ?PendingSocialRegistration;

    public function set(PendingSocialRegistration $registration): void;

    public function clear(): void;
}
