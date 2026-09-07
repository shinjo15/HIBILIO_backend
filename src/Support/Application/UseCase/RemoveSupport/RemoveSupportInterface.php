<?php

declare(strict_types=1);

namespace Src\Support\Application\UseCase\RemoveSupport;

interface RemoveSupportInterface
{
    public function execute(RemoveSupportInputPort $input): void;
}
