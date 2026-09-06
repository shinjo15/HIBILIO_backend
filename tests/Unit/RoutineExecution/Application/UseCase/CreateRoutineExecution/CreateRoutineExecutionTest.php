<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecution;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInput;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionTest extends TestCase
{
    public function test_saves_an_execution_and_only_the_selected_actions_in_one_transaction(): void
    {
        $routineExecutionRepository = new class implements RoutineExecutionRepositoryInterface
        {
            public ?RoutineExecution $savedRoutineExecution = null;

            public function save(RoutineExecution $routineExecution): void
            {
                $this->savedRoutineExecution = $routineExecution;
            }
        };
        $routineExecutionActionRepository = new class implements RoutineExecutionActionRepositoryInterface
        {
            /** @var list<RoutineExecutionAction> */
            public array $savedRoutineExecutionActions = [];

            public function save(RoutineExecutionAction $routineExecutionAction): void
            {
                $this->savedRoutineExecutionActions[] = $routineExecutionAction;
            }
        };
        $transactionManager = new class implements TransactionManagerInterface
        {
            public int $transactionCount = 0;

            public function transaction(callable $callback): mixed
            {
                $this->transactionCount++;

                return $callback();
            }
        };
        $routineActionRepository = new class implements RoutineActionRepositoryInterface
        {
            public int $findByIdentifiersCount = 0;

            public function findByIdentifiers(array $routineActionIdentifiers): array
            {
                $this->findByIdentifiersCount++;

                return array_map(
                    static fn (RoutineActionIdentifier $routineActionIdentifier): RoutineAction => RoutineAction::create(
                        $routineActionIdentifier,
                        null,
                        new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
                        new RoutineActionName('実行した行動'),
                        null,
                        null,
                    ),
                    $routineActionIdentifiers,
                );
            }

            public function save(RoutineAction $routineAction): void {}
        };

        $useCase = new CreateRoutineExecution(
            transactionManager: $transactionManager,
            routineExecutionFactory: new class implements RoutineExecutionFactoryInterface
            {
                public function create(
                    AccountIdentifier $executorAccountIdentifier,
                    RoutineIdentifier $routineIdentifier,
                    ?RoutineExecutionMemo $routineExecutionMemo,
                ): RoutineExecution {
                    return new RoutineExecution(
                        new RoutineExecutionIdentifier('cccccccc-cccc-4ccc-8ccc-cccccccccccc'),
                        $executorAccountIdentifier,
                        $routineIdentifier,
                        new ExecutedAt(new DateTimeImmutable('2026-09-06 12:34:56')),
                        null,
                    );
                }
            },
            routineExecutionActionFactory: new class implements RoutineExecutionActionFactoryInterface
            {
                public function create(
                    RoutineExecutionIdentifier $routineExecutionIdentifier,
                    RoutineActionIdentifier $routineActionIdentifier,
                ): RoutineExecutionAction {
                    return new RoutineExecutionAction(
                        $routineExecutionIdentifier,
                        $routineActionIdentifier,
                    );
                }
            },
            routineExecutionRepository: $routineExecutionRepository,
            routineExecutionActionRepository: $routineExecutionActionRepository,
            routineActionRepository: $routineActionRepository,

        );

        $useCase->execute(new CreateRoutineExecutionInput(
            new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'),
            new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'),
            [
                new RoutineActionIdentifier('10000000-0000-4000-8000-000000000001'),
                new RoutineActionIdentifier('10000000-0000-4000-8000-000000000003'),
            ],
            null,
        ));

        self::assertSame(1, $transactionManager->transactionCount);
        self::assertSame(1, $routineActionRepository->findByIdentifiersCount);
        self::assertSame(
            'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            $routineExecutionRepository->savedRoutineExecution?->routineExecutionIdentifier()->value(),
        );
        self::assertSame([
            '10000000-0000-4000-8000-000000000001',
            '10000000-0000-4000-8000-000000000003',
        ], array_map(
            static fn (RoutineExecutionAction $routineExecutionAction): string => $routineExecutionAction->routineActionIdentifier()->value(),
            $routineExecutionActionRepository->savedRoutineExecutionActions,
        ));
    }
}
