<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraft;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionDraftFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftTest extends TestCase
{
    public function test_creates_and_saves_the_draft_without_a_result(): void
    {
        $account = new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $routine = new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $draft = new RoutineExecutionDraft(
            new RoutineExecutionIdentifier('cccccccc-cccc-4ccc-8ccc-cccccccccccc'),
            $account,
            $routine,
            new ExecutedAt(new DateTimeImmutable('2026-09-06 12:34:56')),
            null,
        );
        $factory = $this->createMock(RoutineExecutionDraftFactoryInterface::class);
        $factory->expects(self::once())->method('create')->with(self::identicalTo($account), self::identicalTo($routine))->willReturn($draft);
        $repository = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $repository->expects(self::once())->method('save')->with(self::identicalTo($draft));

        $useCase = new CreateRoutineExecutionDraft($factory, $repository);

        self::assertNull($useCase->execute(new CreateRoutineExecutionDraftInput($account, $routine)));
    }
}
