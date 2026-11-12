<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Exception;

final class RoutineNotFoundException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('指定したルーティンが見つかりません。');
    }
}
