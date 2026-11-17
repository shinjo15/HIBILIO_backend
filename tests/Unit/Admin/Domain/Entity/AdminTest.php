<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Admin\Domain\Entity\Admin;
use Src\Admin\Domain\ValueObject\AdminIdentifier;
use Src\Admin\Domain\ValueObject\AdminName;
use Src\Shared\Domain\ValueObject\EmailAddress;

final class AdminTest extends TestCase
{
    public function test_retains_only_admin_domain_values(): void
    {
        $identifier = new AdminIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5');
        $name = new AdminName('管理者');
        $emailAddress = new EmailAddress('admin@example.com');

        $admin = new Admin($identifier, $name, $emailAddress);

        self::assertSame($identifier, $admin->identifier());
        self::assertSame($name, $admin->name());
        self::assertSame($emailAddress, $admin->emailAddress());
        self::assertCount(3, (new \ReflectionClass($admin))->getProperties());
    }

    public function test_rejects_invalid_email_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EmailAddress('not-an-email');
    }
}
