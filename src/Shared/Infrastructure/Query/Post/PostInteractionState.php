<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Query\Post;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PostInteractionState
{
    public static function select(Builder $query, ?string $viewerAccountIdentifier, string $postIdentifierColumn): void
    {
        if ($viewerAccountIdentifier === null) {
            $query
                ->selectRaw('0 as liked')
                ->selectRaw('0 as supported');

            return;
        }

        $query
            ->selectSub(self::exists('likes', $viewerAccountIdentifier, $postIdentifierColumn), 'liked')
            ->selectSub(self::exists('supports', $viewerAccountIdentifier, $postIdentifierColumn), 'supported');
    }

    private static function exists(string $table, string $viewerAccountIdentifier, string $postIdentifierColumn): Builder
    {
        return DB::table($table)
            ->selectRaw('count(*) > 0')
            ->where("{$table}.account_identifier", $viewerAccountIdentifier)
            ->whereColumn("{$table}.post_identifier", $postIdentifierColumn);
    }
}
