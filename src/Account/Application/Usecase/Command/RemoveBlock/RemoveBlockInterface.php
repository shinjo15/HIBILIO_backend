<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveBlock;

interface RemoveBlockInterface
{
    public function execute(RemoveBlockInputPort $input): void;
}
