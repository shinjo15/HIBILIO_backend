<?php

declare(strict_types=1);

namespace Src\Authentication\Application\UseCase\VerifyRegistrationPasscode;

interface VerifyRegistrationPasscodeInterface
{
    public function execute(VerifyRegistrationPasscodeInput $input): VerifyRegistrationPasscodeOutput;
}
