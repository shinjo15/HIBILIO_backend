<?php

declare(strict_types=1);

namespace Src\Contact\Domain\ValueObject;

enum ContactSendStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
