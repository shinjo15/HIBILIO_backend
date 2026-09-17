<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Query\GetMySupports;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInputPort;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInterface;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsOutput;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsOutputPort;

final class GetMySupports implements GetMySupportsInterface
{
    public function execute(GetMySupportsInputPort $input): GetMySupportsOutputPort
    {
        $paginator = DB::table('supports')
            ->join('posts', 'supports.post_identifier', '=', 'posts.post_identifier')
            ->where('supports.account_identifier', $input->accountIdentifier())
            ->where('posts.post_category', 'action')
            ->where('posts.available', true)
            ->orderByDesc('supports.created_at')
            ->orderBy('supports.post_identifier')
            ->paginate($input->numberOfItemsPerPage(), [
                'posts.post_identifier',
                'posts.routine_identifier',
                'posts.routine_execution_identifier',
                'posts.post_category',
                'posts.post_like_count',
                'posts.post_support_count',
                'supports.created_at as supported_at',
            ], 'page', $input->page());

        $items = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'postIdentifier' => (string) $record->post_identifier,
                'routineIdentifier' => (string) $record->routine_identifier,
                'routineExecutionIdentifier' => $record->routine_execution_identifier === null
                    ? null
                    : (string) $record->routine_execution_identifier,
                'postCategory' => (string) $record->post_category,
                'postLikeCount' => (int) $record->post_like_count,
                'postSupportCount' => (int) $record->post_support_count,
                'supportedAt' => (new DateTimeImmutable((string) $record->supported_at))->format(DATE_ATOM),
            ])
            ->values()
            ->all();

        return new GetMySupportsOutput($items, $paginator->total());
    }
}
