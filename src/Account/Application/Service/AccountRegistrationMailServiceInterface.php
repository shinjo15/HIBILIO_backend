<?php

declare(strict_types=1);

namespace Src\Account\Application\Service;

use Src\Account\Domain\ValueObject\AccountName;
use Src\Shared\Domain\ValueObject\EmailAddress;

interface AccountRegistrationMailServiceInterface
{
    public function send(EmailAddress $emailAddress, AccountName $accountName): void;
}
