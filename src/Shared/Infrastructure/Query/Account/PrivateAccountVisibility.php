<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Query\Account;

use Illuminate\Database\Query\Builder;

final class PrivateAccountVisibility
{
    public static function exclude(Builder $query, ?string $viewerAccountIdentifier, string $accountIdentifierColumn, string $visibilityColumn): void
    {
        $query->where(static function (Builder $visibilityQuery) use ($viewerAccountIdentifier, $accountIdentifierColumn, $visibilityColumn): void {
            $visibilityQuery->where($visibilityColumn, 'public');

            if ($viewerAccountIdentifier === null) {
                return;
            }

            $visibilityQuery
                ->orWhere($accountIdentifierColumn, $viewerAccountIdentifier)
                ->orWhereExists(static function (Builder $followsQuery) use ($viewerAccountIdentifier, $accountIdentifierColumn): void {
                    $followsQuery
                        ->selectRaw('1')
                        ->from('follows')
                        ->where('follows.following_account_identifier', $viewerAccountIdentifier)
                        ->whereColumn('follows.followed_account_identifier', $accountIdentifierColumn);
                });
        });
    }
}
