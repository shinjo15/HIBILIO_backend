<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\UpdateAccountProfile;

interface UpdateAccountProfileInterface
{
    public function execute(UpdateAccountProfileInputPort $input): void;
}
