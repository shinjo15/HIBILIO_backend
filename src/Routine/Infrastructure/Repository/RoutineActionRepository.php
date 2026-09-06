<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Repository;

use App\Models\RoutineActionModel;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineActionRepository implements RoutineActionRepositoryInterface
{
    public function findByIdentifiers(array $routineActionIdentifiers): array
    {
        if ($routineActionIdentifiers === []) {
            return [];
        }

        return RoutineActionModel::query()
            ->whereIn('routine_action_identifier', array_map(
                static fn (RoutineActionIdentifier $routineActionIdentifier): string => $routineActionIdentifier->value(),
                $routineActionIdentifiers,
            ))
            ->get()
            ->map(static fn (RoutineActionModel $routineAction): RoutineAction => RoutineAction::create(
                new RoutineActionIdentifier($routineAction->routine_action_identifier),
                $routineAction->parent_routine_action_identifier === null
                    ? null
                    : new RoutineActionIdentifier($routineAction->parent_routine_action_identifier),
                new RoutineIdentifier($routineAction->routine_identifier),
                new RoutineActionName($routineAction->action_name),
                $routineAction->action_memo === null
                    ? null
                    : new RoutineActionMemo($routineAction->action_memo),
                $routineAction->action_minutes === null
                    ? null
                    : new RoutineActionMinutes($routineAction->action_minutes),
            ))
            ->all();
    }

    public function save(RoutineAction $routineAction): void
    {
        RoutineActionModel::query()->create([
            'routine_action_identifier' => $routineAction->routineActionIdentifier()->value(),
            'parent_routine_action_identifier' => $routineAction->parentRoutineActionIdentifier()?->value(),
            'routine_identifier' => $routineAction->routineIdentifier()->value(),
            'action_name' => $routineAction->routineActionName()->value(),
            'action_memo' => $routineAction->routineActionMemo()?->value(),
            'action_minutes' => $routineAction->routineActionMinutes()?->value(),
            'available' => true,
        ]);
    }
}
