<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

enum FollowRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
