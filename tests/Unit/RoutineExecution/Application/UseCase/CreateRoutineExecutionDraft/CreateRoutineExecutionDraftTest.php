<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsOutput;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraft;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\RoutineExecution\Domain\Entity\Account;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Exception\AccountNotFoundException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftTest extends TestCase
{
    #[DataProvider('draftStates')]
    public function test_creates_only_when_no_draft_exists(bool $exists): void
    {
        $accountId = new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $routineId = new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $accounts = $this->createMock(AccountRepositoryInterface::class);
        $accounts->expects(self::once())->method('findForUpdate')->with($accountId)->willReturn(new Account($accountId, 0));
        $accounts->expects(self::never())->method('save');

        $routines = $this->createMock(RoutineRepositoryInterface::class);
        $routines->expects(self::once())->method('exists')->with($routineId)->willReturn(true);
        $details = $this->createMock(GetRoutineDetailsInterface::class);
        $details->expects(self::once())->method('execute')->willReturn(new GetRoutineDetailsOutput([]));
        $drafts = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->expects(self::once())->method('exists')->with($accountId, $routineId)->willReturn($exists);
        $factory = $this->createMock(RoutineExecutionFactoryInterface::class);
        if ($exists) {
            $factory->expects(self::never())->method('create');
            $drafts->expects(self::never())->method('save');
        } else {
            $draft = new RoutineExecution(new RoutineExecutionIdentifier('cccccccc-cccc-4ccc-8ccc-cccccccccccc'), $accountId, $routineId, new ExecutedAt(new \DateTimeImmutable), null);
            $factory->expects(self::once())->method('create')->with($accountId, $routineId, null)->willReturn($draft);
            $drafts->expects(self::once())->method('save')->with($draft);
        }
        $transaction = new class implements TransactionManagerInterface
        {
            public function transaction(callable $callback): mixed
            {
                return $callback();
            }
        };
        $useCase = new CreateRoutineExecutionDraft($transaction, $accounts, $routines, $details, $drafts, $factory);

        self::assertSame(! $exists, $useCase->execute(new CreateRoutineExecutionDraftInput($accountId, $routineId))->created());
    }

    public function test_missing_account_throws_before_creating_a_draft(): void
    {
        $accountId = new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $routineId = new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $accounts = $this->createMock(AccountRepositoryInterface::class);
        $accounts->expects(self::once())->method('findForUpdate')->with($accountId)->willReturn(null);
        $routines = $this->createMock(RoutineRepositoryInterface::class);
        $routines->expects(self::never())->method('exists');
        $drafts = $this->createMock(RoutineExecutionDraftRepositoryInterface::class);
        $drafts->expects(self::never())->method('save');
        $transaction = new class implements TransactionManagerInterface
        {
            public function transaction(callable $callback): mixed
            {
                return $callback();
            }
        };
        $useCase = new CreateRoutineExecutionDraft($transaction, $accounts, $routines, $this->createStub(GetRoutineDetailsInterface::class), $drafts, $this->createStub(RoutineExecutionFactoryInterface::class));

        $this->expectException(AccountNotFoundException::class);
        $useCase->execute(new CreateRoutineExecutionDraftInput($accountId, $routineId));
    }

    public static function draftStates(): array
    {
        return ['new' => [false], 'existing' => [true]];
    }
}
