<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

enum AccountUiMode: string
{
    case SYSTEM = 'system';
    case LIGHT = 'light';
    case DARK = 'dark';
}
