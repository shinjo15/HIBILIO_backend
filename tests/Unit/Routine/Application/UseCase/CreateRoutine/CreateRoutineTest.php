<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Application\UseCase\CreateRoutine;

use PHPUnit\Framework\TestCase;
use Src\Post\Domain\Entity\Post;
use Src\Post\Domain\Factory\PostFactoryInterface;
use Src\Post\Domain\Repository\PostRepositoryInterface;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutine;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineActionInput;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineInput;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Factory\RoutineActionFactoryInterface;
use Src\Routine\Domain\Factory\RoutineFactoryInterface;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class CreateRoutineTest extends TestCase
{
    public function test_creates_a_routine_actions_tags_and_routine_post_in_one_transaction(): void
    {
        $routineRepository = new class implements RoutineRepositoryInterface
        {
            public ?Routine $savedRoutine = null;

            public function save(Routine $routine): void
            {
                $this->savedRoutine = $routine;
            }
        };
        $routineActionRepository = new class implements RoutineActionRepositoryInterface
        {
            /** @var list<RoutineAction> */
            public array $savedRoutineActions = [];

            public function findByIdentifiers(array $routineActionIdentifiers): array
            {
                return [];
            }

            public function save(RoutineAction $routineAction): void
            {
                $this->savedRoutineActions[] = $routineAction;
            }
        };
        $postRepository = new class implements PostRepositoryInterface
        {
            public ?Post $savedPost = null;

            public function find(PostIdentifier $postIdentifier): ?Post
            {
                return null;
            }

            public function save(Post $post): void
            {
                $this->savedPost = $post;
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
        $postFactory = new class implements PostFactoryInterface
        {
            public function createRoutinePost(RoutineIdentifier $routineIdentifier): Post
            {
                return Post::create(
                    postIdentifier: new PostIdentifier('e1954b83-b532-40ae-8b9e-49d488040d0f'),
                    routineIdentifier: $routineIdentifier,
                    routineExecutionIdentifier: null,
                    postCategory: PostCategory::ROUTINE,
                    postLikeCount: new PostLikeCount(0),
                    postSupportCount: new PostSupportCount(0),
                );
            }

            public function createActionPost(
                RoutineIdentifier $routineIdentifier,
                RoutineExecutionIdentifier $routineExecutionIdentifier,
            ): Post {
                throw new \LogicException('ルーティン作成では実行投稿を作成しません。');
            }
        };
        $creationOrder = new \ArrayObject;

        $useCase = new CreateRoutine(
            uuidService: new class implements UuidServiceInterface
            {
                /** @var list<string> */
                private array $values = [
                    '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3',
                    'f6ca9f4c-169b-4b2d-a717-4a4f40d1490f',
                    '75017745-e475-4337-b0f3-3fc3d670e5c7',
                ];

                public function generate(): string
                {
                    return array_shift($this->values);
                }
            },
            routineFactory: new class($creationOrder) implements RoutineFactoryInterface
            {
                public function __construct(
                    private \ArrayObject $creationOrder,
                ) {}

                public function create(
                    RoutineIdentifier $routineIdentifier,
                    ?RoutineIdentifier $parentRoutineIdentifier,
                    RoutineName $routineName,
                    RoutineActionIdentifiers $routineActionIdentifiers,
                    ?RoutineMemo $routineMemo,
                    AccountIdentifier $accountIdentifier,
                    ?RoutineExecutionMinutes $routineExecutionMinutes,
                    ?RoutineTagIdentifiers $routineTagIdentifiers,
                ): Routine {
                    $this->creationOrder[] = 'routine';

                    return Routine::create(
                        $routineIdentifier,
                        $parentRoutineIdentifier,
                        $routineName,
                        $routineActionIdentifiers,
                        $routineMemo,
                        $accountIdentifier,
                        $routineExecutionMinutes,
                        $routineTagIdentifiers,
                    );
                }
            },
            routineActionFactory: new class($creationOrder) implements RoutineActionFactoryInterface
            {
                public function __construct(
                    private \ArrayObject $creationOrder,
                ) {}

                public function create(
                    RoutineActionIdentifier $routineActionIdentifier,
                    ?RoutineActionIdentifier $parentRoutineActionIdentifier,
                    RoutineIdentifier $routineIdentifier,
                    RoutineActionName $routineActionName,
                    ?RoutineActionMemo $routineActionMemo,
                    ?RoutineActionMinutes $routineActionMinutes,
                ): RoutineAction {
                    $this->creationOrder[] = 'action';

                    return RoutineAction::create(
                        $routineActionIdentifier,
                        $parentRoutineActionIdentifier,
                        $routineIdentifier,
                        $routineActionName,
                        $routineActionMemo,
                        $routineActionMinutes,
                    );
                }
            },
            postFactory: $postFactory,
            routineRepository: $routineRepository,
            routineActionRepository: $routineActionRepository,
            postRepository: $postRepository,
            transactionManager: $transactionManager,
        );

        $useCase->execute(new CreateRoutineInput(
            accountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            parentRoutineIdentifier: null,
            routineName: new RoutineName('朝の集中ルーティン'),
            routineActions: [
                new CreateRoutineActionInput(
                    routineActionName: new RoutineActionName('水を飲む'),
                    routineActionMemo: null,
                    routineActionMinutes: new RoutineActionMinutes(1),
                    parentRoutineActionIndex: null,
                ),
                new CreateRoutineActionInput(
                    routineActionName: new RoutineActionName('ストレッチ'),
                    routineActionMemo: new RoutineActionMemo('肩を中心に伸ばす'),
                    routineActionMinutes: new RoutineActionMinutes(5),
                    parentRoutineActionIndex: 0,
                ),
            ],
            routineMemo: new RoutineMemo('仕事前に集中力を高める'),
            routineExecutionMinutes: new RoutineExecutionMinutes(40),
            routineTagIdentifiers: new RoutineTagIdentifiers([
                new TagIdentifier('b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'),
            ]),
        ));

        $this->assertSame(1, $transactionManager->transactionCount);
        $this->assertSame(['action', 'action', 'routine'], $creationOrder->getArrayCopy());
        $this->assertSame('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', $routineRepository->savedRoutine?->routineIdentifier()->value());
        $this->assertSame(['b0caa7f4-e1da-4f48-a8db-12fcf9bf47d5'], array_map(
            static fn (TagIdentifier $identifier): string => $identifier->value(),
            $routineRepository->savedRoutine?->routineTagIdentifiers()?->values() ?? [],
        ));
        $this->assertCount(2, $routineActionRepository->savedRoutineActions);
        $this->assertNull($routineActionRepository->savedRoutineActions[0]->parentRoutineActionIdentifier());
        $this->assertSame(
            $routineActionRepository->savedRoutineActions[0]->routineActionIdentifier(),
            $routineActionRepository->savedRoutineActions[1]->parentRoutineActionIdentifier(),
        );
        $this->assertSame('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', $postRepository->savedPost?->routineIdentifier()->value());
        $this->assertSame(PostCategory::ROUTINE, $postRepository->savedPost?->postCategory());
    }
}
