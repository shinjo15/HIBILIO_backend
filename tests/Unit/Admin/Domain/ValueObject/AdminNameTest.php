<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Src\Admin\Domain\ValueObject\AdminName;

final class AdminNameTest extends TestCase
{
    public function test_accepts_a_name_of_50_multibyte_characters(): void
    {
        $name = str_repeat('あ', 50);

        self::assertSame($name, (new AdminName($name))->value());
    }

    public function test_rejects_a_name_of_51_multibyte_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AdminName(str_repeat('あ', 51));
    }

    public function test_rejects_an_empty_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AdminName('');
    }

    public function test_rejects_a_blank_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AdminName('   ');
    }
}
