<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetMyRoutineExecutionDrafts;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsOutput;
use Src\RoutineExecution\Application\Usecase\Query\GetMyRoutineExecutionDrafts\GetMyRoutineExecutionDraftsOutputPort;

final class GetMyRoutineExecutionDrafts implements GetMyRoutineExecutionDraftsInterface
{
    public function execute(GetMyRoutineExecutionDraftsInputPort $input): GetMyRoutineExecutionDraftsOutputPort
    {
        $rows = DB::table('routine_execution_drafts as drafts')
            ->leftJoin('routine_execution_action_drafts as actions', 'drafts.routine_execution_identifier', '=', 'actions.routine_execution_identifier')
            ->where('drafts.executor_account_identifier', $input->accountIdentifier())
            ->orderBy('drafts.routine_execution_identifier')
            ->orderBy('actions.routine_action_identifier')
            ->get(['drafts.routine_execution_identifier', 'drafts.routine_identifier', 'drafts.executed_at', 'drafts.routine_execution_memo', 'actions.routine_action_identifier']);

        $items = [];
        foreach ($rows as $row) {
            $identifier = (string) $row->routine_execution_identifier;
            if (! isset($items[$identifier])) {
                $items[$identifier] = [
                    'routineExecutionIdentifier' => $identifier,
                    'routineIdentifier' => (string) $row->routine_identifier,
                    'executedAt' => (new DateTimeImmutable((string) $row->executed_at, new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.uP'),
                    'routineExecutionMemo' => $row->routine_execution_memo === null ? null : (string) $row->routine_execution_memo,
                    'executedRoutineActionIdentifiers' => [],
                ];
            }
            if ($row->routine_action_identifier !== null) {
                $items[$identifier]['executedRoutineActionIdentifiers'][] = $row->routine_action_identifier;
            }
        }

        return new GetMyRoutineExecutionDraftsOutput(array_values($items));
    }
}
