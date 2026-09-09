<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateBlock;

interface CreateBlockInterface
{
    public function execute(CreateBlockInputPort $input): void;
}
