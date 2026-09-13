<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

enum SocialLoginProvider: string
{
    case GOOGLE = 'google';
    case APPLE = 'apple';
}
