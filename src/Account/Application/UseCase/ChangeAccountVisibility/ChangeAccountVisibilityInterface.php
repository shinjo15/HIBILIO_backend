<?php

declare(strict_types=1);

namespace Src\Account\Application\UseCase\ChangeAccountVisibility;

interface ChangeAccountVisibilityInterface
{
    public function execute(ChangeAccountVisibilityInputPort $input): void;
}
