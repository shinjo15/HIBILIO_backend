<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Repository;

use App\Models\RoutineModel;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class RoutineRepository implements RoutineRepositoryInterface
{
    public function exists(RoutineIdentifier $routineIdentifier): bool
    {
        return RoutineModel::query()->whereKey($routineIdentifier->value())->exists();
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
