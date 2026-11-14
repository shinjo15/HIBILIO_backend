<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RegisterListeningContent;

interface RegisterListeningContentInterface
{
    public function execute(RegisterListeningContentInputPort $input): void;
}
