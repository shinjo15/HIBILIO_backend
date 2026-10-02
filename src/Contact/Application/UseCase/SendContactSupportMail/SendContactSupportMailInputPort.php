<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface SendContactSupportMailInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function title(): ContactTitle;

    public function content(): ContactContent;
}
