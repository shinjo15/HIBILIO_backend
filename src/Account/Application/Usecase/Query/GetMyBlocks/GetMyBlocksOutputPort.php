<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

interface GetMyBlocksOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, accountBio: ?string}> */
    public function blocks(): array;
}
