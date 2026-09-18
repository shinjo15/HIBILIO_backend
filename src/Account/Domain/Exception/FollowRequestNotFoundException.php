<?php

declare(strict_types=1);

namespace Src\Account\Domain\Exception;

use DomainException;

final class FollowRequestNotFoundException extends DomainException
{
    protected $message = 'フォローリクエストが見つかりません。';
}
