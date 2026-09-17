<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Query\Post;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PostInteractionState
{
    public static function selectLiked(Builder $query, ?string $viewerAccountIdentifier, string $postIdentifierColumn): void
    {
        self::select($query, 'likes', 'liked', $viewerAccountIdentifier, $postIdentifierColumn);
    }

    public static function selectSupported(Builder $query, ?string $viewerAccountIdentifier, string $postIdentifierColumn): void
    {
        self::select($query, 'supports', 'supported', $viewerAccountIdentifier, $postIdentifierColumn);
    }

    public static function selectBoth(Builder $query, ?string $viewerAccountIdentifier, string $postIdentifierColumn): void
    {
        self::selectLiked($query, $viewerAccountIdentifier, $postIdentifierColumn);
        self::selectSupported($query, $viewerAccountIdentifier, $postIdentifierColumn);
    }

    private static function select(Builder $query, string $table, string $alias, ?string $viewerAccountIdentifier, string $postIdentifierColumn): void
    {
        if ($viewerAccountIdentifier === null) {
            $query->selectRaw("0 as {$alias}");

            return;
        }

        $query->selectSub(self::exists($table, $viewerAccountIdentifier, $postIdentifierColumn), $alias);
    }

    private static function exists(string $table, string $viewerAccountIdentifier, string $postIdentifierColumn): Builder
    {
        return DB::table($table)
            ->selectRaw('count(*) > 0')
            ->where("{$table}.account_identifier", $viewerAccountIdentifier)
            ->whereColumn("{$table}.post_identifier", $postIdentifierColumn);
    }
}
