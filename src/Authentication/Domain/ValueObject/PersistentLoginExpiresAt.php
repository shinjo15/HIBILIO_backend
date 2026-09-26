<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use DateTimeImmutable;

final readonly class PersistentLoginExpiresAt
{
    public const EXPIRATION_DAYS = 30;

    public function __construct(
        private DateTimeImmutable $value,
    ) {}

    public static function createFromIssuedAt(DateTimeImmutable $issuedAt): self
    {
        return new self($issuedAt->modify(sprintf('+%d days', self::EXPIRATION_DAYS)));
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }
}
