<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Shared\Domain\ValueObject\Base\IntegerValueObject;
use Src\Shared\Domain\ValueObject\Base\StringValueObject;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class RoutineTest extends TestCase
{
    public function test_retains_the_values_of_a_created_routine(): void
    {
        $routineIdentifier = new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3');
        $routineName = new RoutineName('朝の集中ルーティン');
        $actionIdentifiers = new RoutineActionIdentifiers([
            new RoutineActionIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineActionIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7'),
        ]);
        $routineMemo = new RoutineMemo('仕事前に集中力を高める');
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $routineExecutionMinutes = new RoutineExecutionMinutes(40);
        $tagIdentifiers = new RoutineTagIdentifiers([
            new TagIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'),
        ]);

        $routine = Routine::create(
            routineIdentifier: $routineIdentifier,
            parentRoutineIdentifier: null,
            routineName: $routineName,
            routineActionIdentifiers: $actionIdentifiers,
            routineMemo: $routineMemo,
            accountIdentifier: $accountIdentifier,
            routineExecutionMinutes: $routineExecutionMinutes,
            routineTagIdentifiers: $tagIdentifiers,
        );

        $this->assertSame($routineIdentifier, $routine->routineIdentifier());
        $this->assertNull($routine->parentRoutineIdentifier());
        $this->assertSame($routineName, $routine->routineName());
        $this->assertSame($actionIdentifiers, $routine->routineActionIdentifiers());
        $this->assertSame($routineMemo, $routine->routineMemo());
        $this->assertSame($accountIdentifier, $routine->accountIdentifier());
        $this->assertSame($routineExecutionMinutes, $routine->routineExecutionMinutes());
        $this->assertSame($tagIdentifiers, $routine->routineTagIdentifiers());
    }

    public function test_rejects_a_routine_without_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ルーティンには少なくとも1つの行動が必要です。');

        new RoutineActionIdentifiers([]);
    }

    public function test_rejects_a_non_positive_execution_minutes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ルーティンの想定所要時間は1分以上である必要があります。');

        new RoutineExecutionMinutes(0);
    }

    public function test_rejects_a_non_uuid_identifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('識別子は有効なUUID形式である必要があります。');

        new RoutineIdentifier('routine-1');
    }

    public function test_rejects_a_blank_routine_name_in_japanese(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('文字列の値は空白にできません。');

        new RoutineName('   ');
    }

    public function test_rejects_a_routine_name_longer_than_50_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ルーティン名は50文字以下である必要があります。');

        new RoutineName(str_repeat('あ', 51));
    }

    public function test_rejects_a_routine_memo_longer_than_300_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ルーティンメモは300文字以下である必要があります。');

        new RoutineMemo(str_repeat('あ', 301));
    }

    public function test_creates_a_routine_without_execution_minutes_or_tags(): void
    {
        $routine = Routine::create(
            routineIdentifier: new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            parentRoutineIdentifier: null,
            routineName: new RoutineName('朝の集中ルーティン'),
            routineActionIdentifiers: new RoutineActionIdentifiers([
                new RoutineActionIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            ]),
            routineMemo: null,
            accountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            routineExecutionMinutes: null,
            routineTagIdentifiers: null,
        );

        $this->assertSame(null, $routine->routineExecutionMinutes());
        $this->assertSame(null, $routine->routineTagIdentifiers());
    }

    public function test_uses_shared_scalar_value_object_bases(): void
    {
        $this->assertInstanceOf(StringValueObject::class, new RoutineName('朝の集中ルーティン'));
        $this->assertInstanceOf(StringValueObject::class, new RoutineMemo('仕事前に集中力を高める'));
        $this->assertInstanceOf(IntegerValueObject::class, new RoutineExecutionMinutes(40));
    }
}
