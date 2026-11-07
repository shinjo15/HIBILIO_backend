<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface GetMyNotificationsInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
