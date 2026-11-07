<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Notification\Domain\ValueObject\PersonalNotificationContent;

final class PersonalNotificationContentTest extends TestCase
{
    public function test_preserves_content_without_validation_or_normalization(): void
    {
        foreach (['', " \t\n", " 🌸 通知\n二行目 \r\n", str_repeat('長', 501)] as $value) {
            $content = new PersonalNotificationContent($value);

            self::assertSame($value, $content->value());
            self::assertTrue($content->equals(new PersonalNotificationContent($value)));
        }

        self::assertFalse((new PersonalNotificationContent('通知'))->equals(new PersonalNotificationContent('別の通知')));
        self::assertFalse((new PersonalNotificationContent('01'))->equals(new PersonalNotificationContent('1')));
        self::assertFalse((new PersonalNotificationContent('通知'))->equals(new ContactContent('通知')));
    }
}
