<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

interface GetSentFollowRequestsOutputPort
{
    /** @return list<array{accountIdentifier: string, accountName: string, accountBio: ?string, iconImageUrl: ?string, headerImageUrl: ?string}> */
    public function followRequests(): array;
}
