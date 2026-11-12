<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Post\Domain\Entity\Post;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecution;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInput;
use Src\RoutineExecution\Domain\Entity\Account;
use Src\RoutineExecution\Domain\Entity\RoutineExecution;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionAction;
use Src\RoutineExecution\Domain\Exception\AccountNotFoundException;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionActionFactoryInterface;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionFactoryInterface;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionActionRepositoryInterface;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Factory\PostFactoryInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionTest extends TestCase
{
    public function test_missing_executor_does_not_save_execution_actions_or_post(): void
    {
        $accountId = new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $routineId = new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $actionId = new RoutineActionIdentifier('10000000-0000-4000-8000-000000000001');
        $actions = $this->createStub(RoutineActionRepositoryInterface::class);
        $actions->method('findByIdentifiers')->willReturn([
            RoutineAction::create($actionId, null, $routineId, new RoutineActionName('行動'), null, null, new RoutineActionPosition(0)),
        ]);
        $accounts = $this->createMock(AccountRepositoryInterface::class);
        $accounts->expects(self::once())->method('findForUpdate')->with($accountId)->willReturn(null);
        $accounts->expects(self::never())->method('save');
        $executions = $this->createMock(RoutineExecutionRepositoryInterface::class);
        $executions->expects(self::never())->method('save');
        $executionActions = $this->createMock(RoutineExecutionActionRepositoryInterface::class);
        $executionActions->expects(self::never())->method('save');
        $posts = $this->createMock(PostRepositoryInterface::class);
        $posts->expects(self::never())->method('save');
        $transactions = new class implements TransactionManagerInterface
        {
            public function transaction(callable $callback): mixed
            {
                return $callback();
            }
        };
        $useCase = new CreateRoutineExecution(
            $transactions,
            $this->createStub(RoutineExecutionFactoryInterface::class),
            $this->createStub(RoutineExecutionActionFactoryInterface::class),
            $executions,
            $executionActions,
            $actions,
            $this->createStub(PostFactoryInterface::class),
            $posts,
            $accounts,
        );

        $this->expectException(AccountNotFoundException::class);
        $useCase->execute(new CreateRoutineExecutionInput($accountId, $routineId, [$actionId], null));
    }

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
                        new RoutineActionPosition(0),
                    ),
                    $routineActionIdentifiers,
                );
            }

            public function save(RoutineAction $routineAction): void {}
        };
        $postFactory = new class implements PostFactoryInterface
        {
            public function createRoutinePost(RoutineIdentifier $routineIdentifier): Post
            {
                return Post::create(new PostIdentifier('dddddddd-dddd-4ddd-8ddd-dddddddddddd'), $routineIdentifier, null, PostCategory::ROUTINE, new PostLikeCount(0), new PostSupportCount(0));
            }

            public function createActionPost(RoutineIdentifier $routineIdentifier, RoutineExecutionIdentifier $routineExecutionIdentifier): Post
            {
                return Post::create(new PostIdentifier('dddddddd-dddd-4ddd-8ddd-dddddddddddd'), $routineIdentifier, $routineExecutionIdentifier, PostCategory::ACTION, new PostLikeCount(0), new PostSupportCount(0));
            }
        };
        $postRepository = new class implements PostRepositoryInterface
        {
            public function find(PostIdentifier $postIdentifier): ?Post
            {
                return null;
            }

            public function save(Post $post): void {}
        };

        $account = new Account(new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'), 0);
        $accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $accountRepository->expects(self::once())->method('findForUpdate')->willReturn($account);
        $accountRepository->expects(self::once())->method('save')->with($account);
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
            postFactory: $postFactory,
            postRepository: $postRepository,
            accountRepository: $accountRepository,
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
        self::assertSame(100, $account->experience());
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
