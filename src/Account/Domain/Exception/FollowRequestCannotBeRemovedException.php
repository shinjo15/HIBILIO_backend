<?php

declare(strict_types=1);

namespace Src\Account\Domain\Exception;

use DomainException;

final class FollowRequestCannotBeRemovedException extends DomainException
{
    protected $message = '承認済みのフォローリクエストは取り下げられません。';
}
