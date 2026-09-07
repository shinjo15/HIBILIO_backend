<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\GenerateRegistrationPasscode;

interface GenerateRegistrationPasscodeInterface
{
    public function execute(GenerateRegistrationPasscodeInput $input): GenerateRegistrationPasscodeOutput;
}
