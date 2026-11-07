<?php

declare(strict_types=1);

namespace Src\Notification\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class PersonalNotificationContent extends StringValueObject
{
    protected function validate(string $value): void
    {
        // 既存の通知本文は空文字や空白も保存可能なため、そのまま復元する。
    }
}
