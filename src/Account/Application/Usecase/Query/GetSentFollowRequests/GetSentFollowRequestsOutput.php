<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

final readonly class GetSentFollowRequestsOutput implements GetSentFollowRequestsOutputPort
{
    /** @param list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string, headerImageUrl: ?string}> $followRequests */
    public function __construct(private array $followRequests) {}

    public function followRequests(): array
    {
        return $this->followRequests;
    }
}
