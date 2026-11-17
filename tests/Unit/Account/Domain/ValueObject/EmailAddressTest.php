<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Src\Shared\Domain\ValueObject\Base\StringValueObject;
use Src\Shared\Domain\ValueObject\EmailAddress;

final class EmailAddressTest extends TestCase
{
    public function test_retains_a_valid_email_address(): void
    {
        $emailAddress = new EmailAddress('user@example.com');

        $this->assertSame('user@example.com', $emailAddress->value());
    }

    public function test_rejects_an_invalid_email_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EmailAddress('invalid-email');
    }

    public function test_rejects_an_empty_email_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EmailAddress('');
    }

    public function test_uses_the_shared_string_value_object_base(): void
    {
        $this->assertInstanceOf(StringValueObject::class, new EmailAddress('user@example.com'));
    }
}
