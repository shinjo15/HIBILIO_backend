<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Query\Block;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class BlockVisibility
{
    public static function exclude(Builder $query, string $viewerAccountIdentifier, string $targetAccountIdentifierColumn): void
    {
        $query->whereNotExists(static function (Builder $blockQuery) use ($viewerAccountIdentifier, $targetAccountIdentifierColumn): void {
            $blockQuery
                ->selectRaw('1')
                ->from('blocks')
                ->where(static function (Builder $blockQuery) use ($viewerAccountIdentifier, $targetAccountIdentifierColumn): void {
                    $blockQuery
                        ->where('blocks.blocking_account_identifier', $viewerAccountIdentifier)
                        ->whereColumn('blocks.blocked_account_identifier', $targetAccountIdentifierColumn);
                })
                ->orWhere(static function (Builder $blockQuery) use ($viewerAccountIdentifier, $targetAccountIdentifierColumn): void {
                    $blockQuery
                        ->where('blocks.blocked_account_identifier', $viewerAccountIdentifier)
                        ->whereColumn('blocks.blocking_account_identifier', $targetAccountIdentifierColumn);
                });
        });
    }

    public static function exists(string $viewerAccountIdentifier, string $targetAccountIdentifier): bool
    {
        return DB::table('blocks')
            ->where(static function (Builder $query) use ($viewerAccountIdentifier, $targetAccountIdentifier): void {
                $query
                    ->where('blocking_account_identifier', $viewerAccountIdentifier)
                    ->where('blocked_account_identifier', $targetAccountIdentifier);
            })
            ->orWhere(static function (Builder $query) use ($viewerAccountIdentifier, $targetAccountIdentifier): void {
                $query
                    ->where('blocking_account_identifier', $targetAccountIdentifier)
                    ->where('blocked_account_identifier', $viewerAccountIdentifier);
            })
            ->exists();
    }

    public static function routineIsBlocked(?string $viewerAccountIdentifier, string $routineIdentifier): bool
    {
        if ($viewerAccountIdentifier === null) {
            return false;
        }

        $routineAuthorIdentifier = DB::table('routines')
            ->where('routine_identifier', $routineIdentifier)
            ->value('account_identifier');

        return $routineAuthorIdentifier !== null
            && self::exists($viewerAccountIdentifier, (string) $routineAuthorIdentifier);
    }
}
