<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

interface SendContactSupportMailInterface
{
    public function execute(SendContactSupportMailInputPort $input): void;
}
