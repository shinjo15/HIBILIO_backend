<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyRoutines;

interface GetMyRoutinesOutputPort
{
    public function items(): array;

    public function total(): int;
}
