<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

interface GetMyBlocksInterface
{
    /** @return list<array{accountIdentifier: string, accountName: string}> */
    public function execute(GetMyBlocksInput $input): array;
}
