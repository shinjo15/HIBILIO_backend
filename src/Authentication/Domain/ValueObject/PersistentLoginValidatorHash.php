<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class PersistentLoginValidatorHash extends StringValueObject
{
    protected function invalidMessage(): string
    {
        return '永続ログインバリデータハッシュは空にできません。';
    }
}
