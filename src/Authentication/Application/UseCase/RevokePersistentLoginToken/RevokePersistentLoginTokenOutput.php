<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

final readonly class RevokePersistentLoginTokenOutput implements RevokePersistentLoginTokenOutputPort
{
    public function __construct(private bool $revoked) {}

    public function revoked(): bool
    {
        return $this->revoked;
    }
}
