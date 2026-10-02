<?php

declare(strict_types=1);

namespace Src\Contact\Application\Service;

use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ContactSupportMailServiceInterface
{
    public function send(AccountIdentifier $accountIdentifier, EmailAddress $replyTo, string $title, string $content): void;
}
