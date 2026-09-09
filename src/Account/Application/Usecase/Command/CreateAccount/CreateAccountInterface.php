<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

interface CreateAccountInterface
{
    public function execute(CreateAccountInputPort $input): void;
}
