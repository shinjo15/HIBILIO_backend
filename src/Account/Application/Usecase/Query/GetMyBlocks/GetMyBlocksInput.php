<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyBlocks;

final readonly class GetMyBlocksInput implements GetMyBlocksInputPort
{
    public function __construct(private string $accountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
