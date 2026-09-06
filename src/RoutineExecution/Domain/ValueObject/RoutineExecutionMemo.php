<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class RoutineExecutionMemo extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (mb_strlen($value) > 31) {
            throw new \InvalidArgumentException('実行メモは31文字以下である必要があります。');
        }
    }
}
