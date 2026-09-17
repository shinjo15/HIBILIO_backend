<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Query\GetRoutineExecutionDetails;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsOutput;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetRoutineExecutionDetails implements GetRoutineExecutionDetailsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetRoutineExecutionDetailsInputPort $input): GetRoutineExecutionDetailsOutputPort
    {
        $query = DB::table('routine_executions')
            ->join('posts', 'routine_executions.routine_execution_identifier', '=', 'posts.routine_execution_identifier')
            ->join('routines', 'routine_executions.routine_identifier', '=', 'routines.routine_identifier')
            ->join('accounts', 'routine_executions.executor_account_identifier', '=', 'accounts.account_identifier')
            ->join('accounts as routine_authors', 'routines.account_identifier', '=', 'routine_authors.account_identifier')
            ->where('routine_executions.routine_execution_identifier', $input->routineExecutionIdentifier())
            ->whereColumn('posts.routine_identifier', 'routine_executions.routine_identifier')
            ->where('posts.post_category', 'action')
            ->where('posts.available', true)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active')
            ->where('routine_authors.available', true)
            ->where('routine_authors.status', 'active')
            ->select([
                'routine_executions.routine_execution_identifier',
                'routine_executions.routine_execution_memo',
                'routine_executions.executed_at',
                'routines.routine_identifier',
                'routines.routine_name',
                'routines.routine_memo',
                'accounts.account_identifier',
                'accounts.account_name',
                'posts.created_at as posted_at',
                'posts.post_support_count',
            ]);

        if ($input->accountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->accountIdentifier(), 'accounts.account_identifier');
            BlockVisibility::exclude($query, $input->accountIdentifier(), 'routine_authors.account_identifier');
        }

        $details = $query->first();

        if ($details === null) {
            return new GetRoutineExecutionDetailsOutput(null);
        }

        return new GetRoutineExecutionDetailsOutput([
            'routineExecutionIdentifier' => (string) $details->routine_execution_identifier,
            'routineIdentifier' => (string) $details->routine_identifier,
            'routineName' => (string) $details->routine_name,
            'routineMemo' => $details->routine_memo === null ? null : (string) $details->routine_memo,
            'accountIdentifier' => (string) $details->account_identifier,
            'accountName' => (string) $details->account_name,
            'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $details->account_identifier)),
            'routineExecutionMemo' => $details->routine_execution_memo === null ? null : (string) $details->routine_execution_memo,
            'executedAt' => (new DateTimeImmutable((string) $details->executed_at))->format(DATE_ATOM),
            'postedAt' => (new DateTimeImmutable((string) $details->posted_at))->format(DATE_ATOM),
            'supportCount' => (int) $details->post_support_count,
            'tags' => $this->tags((string) $details->routine_identifier),
            'routineExecutionActions' => $this->actions((string) $details->routine_execution_identifier),
        ]);
    }

    /** @return list<array{tagIdentifier: string, tagName: string}> */
    private function tags(string $routineIdentifier): array
    {
        return DB::table('routine_tags')
            ->join('tags', 'routine_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->where('routine_tags.routine_identifier', $routineIdentifier)
            ->where('routine_tags.available', true)
            ->where('tags.available', true)
            ->orderBy('tags.tag_identifier')
            ->get(['tags.tag_identifier', 'tags.tag_name'])
            ->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
            ])
            ->all();
    }

    /** @return list<array{routineActionIdentifier: string, actionName: string, actionMemo: ?string, actionMinutes: ?int}> */
    private function actions(string $routineExecutionIdentifier): array
    {
        return DB::table('routine_execution_actions')
            ->join('routine_actions', 'routine_execution_actions.routine_action_identifier', '=', 'routine_actions.routine_action_identifier')
            ->where('routine_execution_actions.routine_execution_identifier', $routineExecutionIdentifier)
            ->orderBy('routine_execution_actions.created_at')
            ->orderBy('routine_execution_actions.routine_action_identifier')
            ->get([
                'routine_actions.routine_action_identifier',
                'routine_actions.action_name',
                'routine_actions.action_memo',
                'routine_actions.action_minutes',
            ])
            ->map(static fn (object $action): array => [
                'routineActionIdentifier' => (string) $action->routine_action_identifier,
                'actionName' => (string) $action->action_name,
                'actionMemo' => $action->action_memo === null ? null : (string) $action->action_memo,
                'actionMinutes' => $action->action_minutes === null ? null : (int) $action->action_minutes,
            ])
            ->all();
    }
}
