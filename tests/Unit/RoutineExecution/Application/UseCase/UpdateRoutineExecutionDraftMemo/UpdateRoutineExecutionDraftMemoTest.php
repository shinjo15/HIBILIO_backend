<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo\UpdateRoutineExecutionDraftMemo;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo\UpdateRoutineExecutionDraftMemoInput;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class UpdateRoutineExecutionDraftMemoTest extends TestCase
{
    public function test_updates_and_clears_memo_preserving_identity_and_time(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $routine = new RoutineIdentifier('22222222-2222-4222-8222-222222222222');
        $id = new RoutineExecutionIdentifier('33333333-3333-4333-8333-333333333333');
        $time = new ExecutedAt(new DateTimeImmutable('2026-01-01 12:00:00'));
        $draft = new RoutineExecutionDraft($id, $account, $routine, $time, new RoutineExecutionMemo('旧'));
        $memo = new RoutineExecutionMemo('新');
        $repository = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('find')->with($account, $routine)->willReturn($draft);
        $savedMemos = [];
        $repository->expects(self::exactly(2))->method('updateMemo')->willReturnCallback(function (RoutineExecutionDraft $entity) use (&$savedMemos, $draft, $id, $account, $routine, $time): void {
            self::assertSame($draft, $entity);
            $savedMemos[] = $entity->routineExecutionMemo();
            self::assertSame($id, $entity->routineExecutionIdentifier());
            self::assertSame($account, $entity->executorAccountIdentifier());
            self::assertSame($routine, $entity->routineIdentifier());
            self::assertSame($time, $entity->executedAt());
        });
        $useCase = new UpdateRoutineExecutionDraftMemo($repository);
        $useCase->execute(new UpdateRoutineExecutionDraftMemoInput($account, $routine, $memo));
        $useCase->execute(new UpdateRoutineExecutionDraftMemoInput($account, $routine, null));
        self::assertSame($memo, $savedMemos[0]);
        self::assertNull($savedMemos[1]);
    }

    public function test_missing_draft_throws_without_saving(): void
    {
        $repository = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->method('find')->willReturn(null);
        $repository->expects(self::never())->method('updateMemo');
        $this->expectException(RoutineExecutionDraftNotFoundException::class);
        (new UpdateRoutineExecutionDraftMemo($repository))->execute(new UpdateRoutineExecutionDraftMemoInput(
            new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
            new RoutineIdentifier('22222222-2222-4222-8222-222222222222'),
            null,
        ));
    }
}
