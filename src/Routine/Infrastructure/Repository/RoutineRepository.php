<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Repository;

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
    public function find(RoutineIdentifier $identifier): ?Routine
    {
        $model = RoutineModel::query()->whereKey($identifier->value())->where('available', true)->lockForUpdate()->first();
        if ($model === null) {
            return null;
        }

        $actions = $model->actions()->where('available', true)->pluck('routine_action_identifier')->map(static fn (string $id): RoutineActionIdentifier => new RoutineActionIdentifier($id))->all();
        $tags = $model->tags()->wherePivot('available', true)->pluck('tags.tag_identifier')->map(static fn (string $id): TagIdentifier => new TagIdentifier($id))->all();

        return Routine::reconstitute(
            $identifier,
            $model->parent_routine_identifier === null ? null : new RoutineIdentifier($model->parent_routine_identifier),
            new RoutineName($model->routine_name),
            RoutineActionIdentifiers::reconstitute($actions),
            $model->routine_memo === null ? null : new RoutineMemo($model->routine_memo),
            new AccountIdentifier($model->account_identifier),
            $model->routine_execution_minutes === null ? null : new RoutineExecutionMinutes((int) $model->routine_execution_minutes),
            $tags === [] ? null : new RoutineTagIdentifiers($tags),
            $model->video_url === null ? null : new RoutineVideoUrl($model->video_url),
        );
    }

    public function saveVideoLink(Routine $routine): void
    {
        $existing = RoutineModel::query()->whereKey($routine->routineIdentifier()->value())->where('available', true)->firstOrFail();
        $existing->video_url = $routine->videoUrl()?->value();
        $existing->save();
    }

    public function add(Routine $routine): void
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
