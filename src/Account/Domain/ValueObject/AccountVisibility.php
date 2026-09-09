<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

enum AccountVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}
