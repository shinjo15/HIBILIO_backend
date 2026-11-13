<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class VisibleRoutineQuery
{
    public static function for(string $routineIdentifier, ?string $viewerAccountIdentifier): Builder
    {
        $query = DB::table('routines')
            ->join('accounts', 'routines.account_identifier', '=', 'accounts.account_identifier')
            ->where('routines.routine_identifier', $routineIdentifier)
            ->where('routines.available', true)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active');

        if ($viewerAccountIdentifier !== null) {
            BlockVisibility::exclude($query, $viewerAccountIdentifier, 'accounts.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $viewerAccountIdentifier, 'accounts.account_identifier', 'accounts.visibility');

        return $query;
    }
}
