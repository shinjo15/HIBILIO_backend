<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetReceivedFollowRequests;

final readonly class GetReceivedFollowRequestsOutput implements GetReceivedFollowRequestsOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string}> $followRequests */
    public function __construct(private array $followRequests) {}

    public function followRequests(): array
    {
        return $this->followRequests;
    }
}
