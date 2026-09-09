<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateFollow;

interface CreateFollowInterface
{
    public function execute(CreateFollowInputPort $input): void;
}
