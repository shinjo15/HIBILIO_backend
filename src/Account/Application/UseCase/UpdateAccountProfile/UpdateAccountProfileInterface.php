<?php

declare(strict_types=1);

namespace Src\Account\Application\UseCase\UpdateAccountProfile;

interface UpdateAccountProfileInterface
{
    public function execute(UpdateAccountProfileInputPort $input): void;
}
