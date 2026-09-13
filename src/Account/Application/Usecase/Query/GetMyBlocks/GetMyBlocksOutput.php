<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

final readonly class GetMyBlocksOutput implements GetMyBlocksOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string}> $blocks */
    public function __construct(private array $blocks) {}

    public function blocks(): array
    {
        return $this->blocks;
    }
}
