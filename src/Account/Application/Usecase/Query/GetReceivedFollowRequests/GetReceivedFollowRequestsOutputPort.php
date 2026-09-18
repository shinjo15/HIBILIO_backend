<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetReceivedFollowRequests;

interface GetReceivedFollowRequestsOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string}> */
    public function followRequests(): array;
}
