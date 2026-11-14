<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft\DeleteRoutineExecutionDraft;
use Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft\DeleteRoutineExecutionDraftInput;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class DeleteRoutineExecutionDraftTest extends TestCase
{
    public function test_finds_and_deletes_the_accounts_draft(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $routine = new RoutineIdentifier('22222222-2222-4222-8222-222222222222');
        $draft = new RoutineExecutionDraft(
            new RoutineExecutionIdentifier('33333333-3333-4333-8333-333333333333'),
            $account,
            $routine,
            new ExecutedAt(new DateTimeImmutable('2026-01-01 12:00:00')),
            null,
        );
        $repository = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->expects(self::once())->method('find')->with($account, $routine)->willReturn($draft);
        $repository->expects(self::once())->method('delete')->with(self::identicalTo($draft));

        (new DeleteRoutineExecutionDraft($repository))->execute(new DeleteRoutineExecutionDraftInput($account, $routine));
    }

    public function test_missing_draft_throws_without_deleting(): void
    {
        $account = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $routine = new RoutineIdentifier('22222222-2222-4222-8222-222222222222');
        $repository = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->expects(self::once())->method('find')->with($account, $routine)->willReturn(null);
        $repository->expects(self::never())->method('delete');

        $this->expectException(RoutineExecutionDraftNotFoundException::class);
        (new DeleteRoutineExecutionDraft($repository))->execute(new DeleteRoutineExecutionDraftInput($account, $routine));
    }
}
