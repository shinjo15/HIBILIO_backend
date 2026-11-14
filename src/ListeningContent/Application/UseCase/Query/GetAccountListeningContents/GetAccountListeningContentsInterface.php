<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

interface GetAccountListeningContentsInterface
{
    /** @return list<array{listening_content_identifier: string, url: string}>|null */
    public function execute(string $accountIdentifier, ?string $viewerAccountIdentifier): ?array;
}
