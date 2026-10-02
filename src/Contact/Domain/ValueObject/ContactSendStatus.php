<?php

declare(strict_types=1);

namespace Src\Contact\Domain\ValueObject;

enum ContactSendStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
}
