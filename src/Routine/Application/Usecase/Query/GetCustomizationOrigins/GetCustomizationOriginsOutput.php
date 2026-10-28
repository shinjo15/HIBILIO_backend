<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizationOrigins;

final readonly class GetCustomizationOriginsOutput implements GetCustomizationOriginsOutputPort
{
    /** @param array<string, array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}> $origins */
    public function __construct(private array $origins) {}

    public function originsByRoutineIdentifier(): array
    {
        return $this->origins;
    }
}
