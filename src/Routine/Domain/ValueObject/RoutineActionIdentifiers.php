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
        bool $rehydrating = false,
    ) {
        if ($values === [] && ! $rehydrating) {
            throw new \InvalidArgumentException('ルーティンには少なくとも1つの行動が必要です。');
        }
    }

    /** @param list<RoutineActionIdentifier> $values */
    public static function reconstitute(array $values): self
    {
        return new self($values, true);
    }

    /**
     * @return list<RoutineActionIdentifier>
     */
    public function values(): array
    {
        return $this->values;
    }
}
