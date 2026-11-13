<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraftTest extends TestCase
{
    public function test_changes_and_clears_memo_on_same_instance(): void
    {
        $id = new RoutineExecutionIdentifier('33333333-3333-4333-8333-333333333333');
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $routine = new RoutineIdentifier('22222222-2222-4222-8222-222222222222');
        $time = new ExecutedAt(new DateTimeImmutable('2026-01-01 12:00:00'));
        $draft = new RoutineExecutionDraft($id, $account, $routine, $time, new RoutineExecutionMemo('旧'));
        $sameDraft = $draft;
        $memo = new RoutineExecutionMemo('新');

        $draft->changeMemo($memo);
        self::assertSame($memo, $sameDraft->routineExecutionMemo());
        $draft->changeMemo(null);
        self::assertNull($sameDraft->routineExecutionMemo());
        self::assertSame($id, $sameDraft->routineExecutionIdentifier());
        self::assertSame($account, $sameDraft->executorAccountIdentifier());
        self::assertSame($routine, $sameDraft->routineIdentifier());
        self::assertSame($time, $sameDraft->executedAt());
    }

    public function test_multiple_action_changes_are_reflected_in_current_state(): void
    {
        $execution = new RoutineExecutionIdentifier('33333333-3333-4333-8333-333333333333');
        $draft = new RoutineExecutionDraft($execution, new AccountIdentifier('11111111-1111-4111-8111-111111111111'), new RoutineIdentifier('22222222-2222-4222-8222-222222222222'), new ExecutedAt(new DateTimeImmutable('2026-01-01 12:00:00')), null);
        $a = new RoutineActionIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $b = new RoutineActionIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $draft->changeAction(new RoutineExecutionAction($execution, $a), true);
        $draft->changeAction(new RoutineExecutionAction($execution, $b), true);
        self::assertTrue($draft->hasAction($a));
        self::assertTrue($draft->hasAction($b));
        self::assertCount(2, $draft->actions());

        $draft->changeAction(new RoutineExecutionAction($execution, $a), false);
        self::assertFalse($draft->hasAction($a));
        self::assertTrue($draft->hasAction($b));
        self::assertCount(1, $draft->actions());
    }
}
