<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

interface GetAccountListeningContentsInterface
{
    public function execute(GetAccountListeningContentsInputPort $input): ?GetAccountListeningContentsOutputPort;
}
