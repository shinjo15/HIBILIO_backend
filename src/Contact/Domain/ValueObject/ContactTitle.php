<?php

declare(strict_types=1);

namespace Src\Contact\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class ContactTitle extends StringValueObject
{
    protected function validate(string $value): void
    {
        parent::validate($value);

        if (mb_strlen($value) > 50) {
            throw new \InvalidArgumentException('お問い合わせタイトルは50文字以内で入力してください。');
        }
        if (preg_match('/[\r\n\x{2028}\x{2029}]/u', $value) === 1) {
            throw new \InvalidArgumentException('お問い合わせタイトルに改行は使用できません。');
        }
    }
}
