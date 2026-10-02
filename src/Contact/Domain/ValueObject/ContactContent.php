<?php

declare(strict_types=1);

namespace Src\Contact\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class ContactContent extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (mb_strlen($value) > 500) {
            throw new \InvalidArgumentException('お問い合わせ内容は500文字以内で入力してください。');
        }
    }
}
