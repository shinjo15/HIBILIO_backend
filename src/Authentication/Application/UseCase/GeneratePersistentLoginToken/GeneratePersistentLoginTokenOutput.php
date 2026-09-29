<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GeneratePersistentLoginToken;

use DateTimeImmutable;

final readonly class GeneratePersistentLoginTokenOutput implements GeneratePersistentLoginTokenOutputPort
{
    public function __construct(
        private string $selector,
        private string $rawValidator,
        private DateTimeImmutable $expiresAt,
    ) {}

    public function selector(): string
    {
        return $this->selector;
    }

    public function rawValidator(): string
    {
        return $this->rawValidator;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
