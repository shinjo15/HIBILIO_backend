<?php

declare(strict_types=1);

namespace Src\Routine\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;

final readonly class RoutineActionIdentifiers
{
    /**
     * @param  list<RoutineActionIdentifier>  $values
     */
    public function __construct(
        private array $values,
    ) {
        if ($values === []) {
            throw new \InvalidArgumentException('ルーティンには少なくとも1つの行動が必要です。');
        }
    }

    /**
     * @return list<RoutineActionIdentifier>
     */
    public function values(): array
    {
        return $this->values;
    }
}
