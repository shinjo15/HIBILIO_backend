<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

interface GetMyBlocksInputPort
{
    public function accountIdentifier(): string;
}
