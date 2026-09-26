<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\RevokePersistentLoginToken;

use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

final readonly class RevokePersistentLoginTokenInput implements RevokePersistentLoginTokenInputPort
{
    public function __construct(private PersistentLoginSelector $selector, private string $rawValidator) {}

    public function selector(): PersistentLoginSelector
    {
        return $this->selector;
    }

    public function rawValidator(): string
    {
        return $this->rawValidator;
    }
}
