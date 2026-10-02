<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface SendContactSupportMailInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function title(): string;

    public function content(): string;
}
