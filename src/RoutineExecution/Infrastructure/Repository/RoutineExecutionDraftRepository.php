<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\Routine\Domain\Entity\Routine;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function updateMemo(AccountIdentifier $creator, RoutineIdentifier $routine, ?RoutineExecutionMemo $memo): bool
    {
        $query = fn () => DB::table('routine_execution_drafts')
            ->where('draft_creator_account_identifier', $creator->value())
            ->where('routine_identifier', $routine->value());

        $update = fn () => $query()->update(['routine_execution_memo' => $memo?->value(), 'updated_at' => now()]);

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // MySQL can report zero for an unchanged row; use a current read inside the update transaction.
            return DB::transaction(fn () => $update() > 0 || $query()->lockForUpdate()->first() !== null);
        }

        return $update() > 0;
    }

    /** @param list<RoutineAction> $actions */
    public function save(AccountIdentifier $draftCreatorAccountIdentifier, Routine $routine, array $actions): void
    {
        $key = ['draft_creator_account_identifier' => $draftCreatorAccountIdentifier->value(), 'routine_identifier' => $routine->routineIdentifier()->value()];
        $metadata = DB::table('routines')->where('routine_identifier', $routine->routineIdentifier()->value())->first(['available', 'created_at', 'updated_at']);
        $actionMetadata = DB::table('routine_actions')->where('routine_identifier', $routine->routineIdentifier()->value())
            ->get(['routine_action_identifier', 'available', 'created_at', 'updated_at'])->keyBy('routine_action_identifier');

        DB::table('routine_execution_drafts')->insert($key + [
            'parent_routine_identifier' => $routine->parentRoutineIdentifier()?->value(),
            'account_identifier' => $routine->accountIdentifier()->value(),
            'routine_name' => $routine->routineName()->value(),
            'routine_memo' => $routine->routineMemo()?->value(),
            'routine_execution_minutes' => $routine->routineExecutionMinutes()?->value(),
            'video_url' => $routine->videoUrl()?->value(),
            'available' => $metadata->available,
            'source_created_at' => $metadata->created_at,
            'source_updated_at' => $metadata->updated_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ($actions as $action) {
            $source = $actionMetadata->get($action->routineActionIdentifier()->value());
            DB::table('routine_execution_draft_actions')->insert($key + [
                'routine_action_identifier' => $action->routineActionIdentifier()->value(),
                'parent_routine_action_identifier' => $action->parentRoutineActionIdentifier()?->value(),
                'action_name' => $action->routineActionName()->value(),
                'action_memo' => $action->routineActionMemo()?->value(),
                'action_minutes' => $action->routineActionMinutes()?->value(),
                'position' => $action->position()->value(),
                'video_url' => $action->videoUrl()?->value(),
                'available' => $source->available,
                'source_created_at' => $source->created_at,
                'source_updated_at' => $source->updated_at,
            ]);
        }
        foreach ($routine->routineTagIdentifiers()?->values() ?? [] as $tag) {
            DB::table('routine_execution_draft_tags')->insert($key + ['tag_identifier' => $tag->value()]);
        }
    }
}
