<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Admin\Domain\ValueObject\AdminFunctionIdentifier;
use Src\Admin\Domain\ValueObject\AdminFunctionName;
use Src\Admin\Domain\ValueObject\PermissionGroupIdentifier;
use Src\Admin\Domain\ValueObject\PermissionGroupName;

final class PermissionValueObjectTest extends TestCase
{
    public static function identifierClasses(): array
    {
        return [[AdminFunctionIdentifier::class], [PermissionGroupIdentifier::class]];
    }

    public static function nameClasses(): array
    {
        return [[AdminFunctionName::class], [PermissionGroupName::class]];
    }

    #[DataProvider('identifierClasses')]
    public function test_identifier_accepts_valid_uuid(string $class): void
    {
        $value = 'b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5';

        self::assertSame($value, (new $class($value))->value());
    }

    #[DataProvider('identifierClasses')]
    public function test_identifier_rejects_invalid_uuid(string $class): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new $class('invalid-uuid');
    }

    public function test_function_name_accepts_nonblank_value_without_length_limit(): void
    {
        $value = str_repeat('あ', 256);

        self::assertSame($value, (new AdminFunctionName($value))->value());
    }

    public function test_group_name_accepts_50_multibyte_characters(): void
    {
        $value = str_repeat('あ', 50);

        self::assertSame($value, (new PermissionGroupName($value))->value());
    }

    public function test_group_name_rejects_51_multibyte_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PermissionGroupName(str_repeat('あ', 51));
    }

    #[DataProvider('nameClasses')]
    public function test_name_rejects_empty_value(string $class): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new $class('');
    }

    #[DataProvider('nameClasses')]
    public function test_name_rejects_blank_value(string $class): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new $class(" \t\n");
    }
}
