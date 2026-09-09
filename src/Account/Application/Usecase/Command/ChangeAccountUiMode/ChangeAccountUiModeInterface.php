<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ChangeAccountUiMode;

interface ChangeAccountUiModeInterface
{
    public function execute(ChangeAccountUiModeInputPort $input): void;
}
