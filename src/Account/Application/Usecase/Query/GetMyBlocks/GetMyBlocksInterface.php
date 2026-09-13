<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

interface GetMyBlocksInterface
{
    public function execute(GetMyBlocksInputPort $input): GetMyBlocksOutputPort;
}
