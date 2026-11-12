<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Repository;

use App\Models\RoutineActionModel;
use App\Models\RoutineModel;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class RoutineRepository implements RoutineRepositoryInterface
{
    public function findByIdentifier(RoutineIdentifier $identifier): ?Routine
    {
        $model = RoutineModel::query()->with('tags')->find($identifier->value());
        if ($model === null) {
            return null;
        }

        $actions = RoutineActionModel::query()->where('routine_identifier', $identifier->value())
            ->orderBy('position')->orderBy('routine_action_identifier')->pluck('routine_action_identifier')
            ->map(static fn (string $id): RoutineActionIdentifier => new RoutineActionIdentifier($id))->all();

        return Routine::create(
            $identifier,
            $model->parent_routine_identifier === null ? null : new RoutineIdentifier($model->parent_routine_identifier),
            new RoutineName($model->routine_name),
            new RoutineActionIdentifiers($actions),
            $model->routine_memo === null ? null : new RoutineMemo($model->routine_memo),
            new AccountIdentifier($model->account_identifier),
            $model->routine_execution_minutes === null ? null : new RoutineExecutionMinutes($model->routine_execution_minutes),
            new RoutineTagIdentifiers($model->tags->map(static fn ($tag): TagIdentifier => new TagIdentifier($tag->tag_identifier))->all()),
            $model->video_url === null ? null : new RoutineVideoUrl($model->video_url),
        );
    }

    public function save(Routine $routine): void
    {
        $routineModel = RoutineModel::query()->create([
            'routine_identifier' => $routine->routineIdentifier()->value(),
            'parent_routine_identifier' => $routine->parentRoutineIdentifier()?->value(),
            'routine_name' => $routine->routineName()->value(),
            'routine_memo' => $routine->routineMemo()?->value(),
            'video_url' => $routine->videoUrl()?->value(),
            'account_identifier' => $routine->accountIdentifier()->value(),
            'routine_execution_minutes' => $routine->routineExecutionMinutes()?->value(),
            'available' => true,
        ]);
        $tagIdentifiers = $routine->routineTagIdentifiers()?->values() ?? [];

        if ($tagIdentifiers !== []) {
            $routineModel->tags()->syncWithPivotValues(
                array_map(
                    static fn (TagIdentifier $tagIdentifier): string => $tagIdentifier->value(),
                    $tagIdentifiers,
                ),
                ['available' => true],
            );
        }
    }
}
