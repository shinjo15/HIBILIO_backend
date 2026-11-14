<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

interface GetAccountListeningContentsOutputPort
{
    /** @return list<array{listening_content_identifier: string, url: string}> */
    public function items(): array;
}
