<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

interface GetAccountListeningContentsInputPort
{
    public function accountIdentifier(): string;

    public function viewerAccountIdentifier(): ?string;
}
