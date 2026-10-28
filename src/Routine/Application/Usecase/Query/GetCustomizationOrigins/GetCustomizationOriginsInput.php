<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizationOrigins;

final readonly class GetCustomizationOriginsInput implements GetCustomizationOriginsInputPort
{
    /** @param list<string> $routineIdentifiers */
    public function __construct(private array $routineIdentifiers, private ?string $viewerAccountIdentifier) {}

    public function routineIdentifiers(): array
    {
        return $this->routineIdentifiers;
    }

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
    }
}
