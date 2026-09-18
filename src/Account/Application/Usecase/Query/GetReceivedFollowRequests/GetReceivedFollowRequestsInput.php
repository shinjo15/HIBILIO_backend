<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetReceivedFollowRequests;

final readonly class GetReceivedFollowRequestsInput implements GetReceivedFollowRequestsInputPort
{
    public function __construct(private string $targetAccountIdentifier) {}

    public function targetAccountIdentifier(): string
    {
        return $this->targetAccountIdentifier;
    }
}
