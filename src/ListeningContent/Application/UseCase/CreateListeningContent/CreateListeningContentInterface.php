<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\CreateListeningContent;

interface CreateListeningContentInterface
{
    public function execute(CreateListeningContentInputPort $input): void;
}
