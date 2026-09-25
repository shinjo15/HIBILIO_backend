<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

final readonly class GetSentFollowRequestsInput implements GetSentFollowRequestsInputPort
{
    public function __construct(private string $requestingAccountIdentifier) {}

    public function requestingAccountIdentifier(): string
    {
        return $this->requestingAccountIdentifier;
    }
}
