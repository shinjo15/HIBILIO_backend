<?php

declare(strict_types=1);

namespace Src\Routine\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\IntegerValueObject;

final readonly class RoutineActionPosition extends IntegerValueObject
{
    protected function validate(int $value): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('ルーティン行動の位置は0以上である必要があります。');
        }
    }
}
