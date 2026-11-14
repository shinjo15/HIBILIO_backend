<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

final readonly class GetAccountListeningContentsOutput implements GetAccountListeningContentsOutputPort
{
    /** @param list<array{listening_content_identifier: string, url: string}> $items */
    public function __construct(private array $items) {}

    public function items(): array
    {
        return $this->items;
    }
}
