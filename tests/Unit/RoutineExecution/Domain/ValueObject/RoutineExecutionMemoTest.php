<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;

final class RoutineExecutionMemoTest extends TestCase
{
    public function test_accepts_a_memo_of_up_to_31_characters(): void
    {
        $memo = new RoutineExecutionMemo(str_repeat('あ', 31));

        self::assertSame(str_repeat('あ', 31), $memo->value());
    }

    public function test_rejects_a_memo_longer_than_31_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('実行メモは31文字以下である必要があります。');

        new RoutineExecutionMemo(str_repeat('あ', 32));
    }
}
