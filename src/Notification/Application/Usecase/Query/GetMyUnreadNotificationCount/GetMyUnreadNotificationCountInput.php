<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount;

final readonly class GetMyUnreadNotificationCountInput implements GetMyUnreadNotificationCountInputPort
{
    public function __construct(private string $accountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }
}
