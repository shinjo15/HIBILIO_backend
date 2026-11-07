<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyNotifications;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class GetMyNotificationsInput implements GetMyNotificationsInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private int $page, private int $numberOfItemsPerPage) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
