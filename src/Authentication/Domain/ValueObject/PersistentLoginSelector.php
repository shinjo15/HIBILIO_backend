<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class PersistentLoginSelector extends StringValueObject
{
    protected function invalidMessage(): string
    {
        return '永続ログインセレクタは空にできません。';
    }
}
