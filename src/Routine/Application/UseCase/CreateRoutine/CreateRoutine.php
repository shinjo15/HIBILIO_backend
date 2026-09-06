<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\CreateRoutine;

use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Factory\RoutineActionFactoryInterface;
use Src\Routine\Domain\Factory\RoutineFactoryInterface;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Factory\PostFactoryInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class CreateRoutine implements CreateRoutineInterface
{
    public function __construct(
        private UuidServiceInterface $uuidService,
        private RoutineFactoryInterface $routineFactory,
        private RoutineActionFactoryInterface $routineActionFactory,
        private PostFactoryInterface $postFactory,
        private RoutineRepositoryInterface $routineRepository,
        private RoutineActionRepositoryInterface $routineActionRepository,
        private PostRepositoryInterface $postRepository,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function execute(CreateRoutineInputPort $input): void
    {
        $routineIdentifier = new RoutineIdentifier($this->uuidService->generate());
        $routineActions = $this->createRoutineActions($input, $routineIdentifier);
        $routine = $this->routineFactory->create(
            $routineIdentifier,
            $input->parentRoutineIdentifier(),
            $input->routineName(),
            new RoutineActionIdentifiers(array_map(
                static fn (RoutineAction $routineAction): RoutineActionIdentifier => $routineAction->routineActionIdentifier(),
                $routineActions,
            )),
            $input->routineMemo(),
            $input->accountIdentifier(),
            $input->routineExecutionMinutes(),
            $input->routineTagIdentifiers(),
        );
        $post = $this->postFactory->createRoutinePost($routineIdentifier);

        $this->transactionManager->transaction(function () use ($routine, $routineActions, $post): void {
            $this->routineRepository->save($routine);

            foreach ($routineActions as $routineAction) {
                $this->routineActionRepository->save($routineAction);
            }

            $this->postRepository->save($post);
        });
    }

    /** @return list<RoutineAction> */
    private function createRoutineActions(
        CreateRoutineInputPort $input,
        RoutineIdentifier $routineIdentifier,
    ): array {
        $routineActions = [];

        foreach ($input->routineActions() as $index => $routineActionInput) {
            $parentRoutineActionIdentifier = $this->parentRoutineActionIdentifier(
                $routineActionInput->parentRoutineActionIndex(),
                $index,
                $routineActions,
            );
            $routineActions[] = $this->routineActionFactory->create(
                new RoutineActionIdentifier($this->uuidService->generate()),
                $parentRoutineActionIdentifier,
                $routineIdentifier,
                $routineActionInput->routineActionName(),
                $routineActionInput->routineActionMemo(),
                $routineActionInput->routineActionMinutes(),
            );
        }

        return $routineActions;
    }

    /** @param list<RoutineAction> $routineActions */
    private function parentRoutineActionIdentifier(
        ?int $parentRoutineActionIndex,
        int $routineActionIndex,
        array $routineActions,
    ): ?RoutineActionIdentifier {
        if ($parentRoutineActionIndex === null) {
            return null;
        }

        if ($parentRoutineActionIndex >= $routineActionIndex) {
            throw new \InvalidArgumentException('親行動は先に定義された行動を指定する必要があります。');
        }

        return $routineActions[$parentRoutineActionIndex]?->routineActionIdentifier()
            ?? throw new \InvalidArgumentException('親行動のインデックスが行動一覧に存在しません。');
    }
}
