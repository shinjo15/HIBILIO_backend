<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Query\Account;

use Illuminate\Database\Query\Builder;

final class PrivateAccountVisibility
{
    public static function exclude(Builder $query, ?string $viewerAccountIdentifier, string $targetAccountIdentifierColumn, string $targetVisibilityColumn): void
    {
        $query->where(static function (Builder $visibilityQuery) use ($viewerAccountIdentifier, $targetAccountIdentifierColumn, $targetVisibilityColumn): void {
            $visibilityQuery->where($targetVisibilityColumn, 'public');

            if ($viewerAccountIdentifier === null) {
                return;
            }

            $visibilityQuery
                ->orWhere($targetAccountIdentifierColumn, $viewerAccountIdentifier)
                ->orWhereExists(static function (Builder $followQuery) use ($viewerAccountIdentifier, $targetAccountIdentifierColumn): void {
                    $followQuery
                        ->selectRaw('1')
                        ->from('follows')
                        ->where('follows.following_account_identifier', $viewerAccountIdentifier)
                        ->whereColumn('follows.followed_account_identifier', $targetAccountIdentifierColumn);
                });
        });
    }
}
