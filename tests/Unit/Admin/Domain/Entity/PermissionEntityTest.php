<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Admin\Domain\Entity\AdminFunction;
use Src\Admin\Domain\Entity\PermissionGroup;
use Src\Admin\Domain\ValueObject\AdminFunctionIdentifier;
use Src\Admin\Domain\ValueObject\AdminFunctionName;
use Src\Admin\Domain\ValueObject\PermissionGroupIdentifier;
use Src\Admin\Domain\ValueObject\PermissionGroupName;

final class PermissionEntityTest extends TestCase
{
    public function test_function_retains_its_identifier_and_name(): void
    {
        $identifier = new AdminFunctionIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5');
        $name = new AdminFunctionName('userUpdate');
        $function = new AdminFunction($identifier, $name);

        self::assertSame($identifier, $function->identifier());
        self::assertSame($name, $function->name());
    }

    public function test_permission_group_retains_its_identifier_and_name(): void
    {
        $identifier = new PermissionGroupIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5');
        $name = new PermissionGroupName('管理者');
        $group = new PermissionGroup($identifier, $name);

        self::assertSame($identifier, $group->identifier());
        self::assertSame($name, $group->name());
    }
}
