<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Repository\RoutineExecutionDraftRepositoryInterface;

final class RoutineExecutionDraftRepository implements RoutineExecutionDraftRepositoryInterface
{
    public function save(RoutineExecutionDraft $draft): void
    {
        $routine = $draft->routine;
        $key = ['draft_creator_account_identifier' => $draft->draftCreatorAccountIdentifier->value(), 'routine_identifier' => $routine->routineIdentifier()->value()];
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
        foreach ($draft->actions as $action) {
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
