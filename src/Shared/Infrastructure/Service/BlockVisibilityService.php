<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Src\Shared\Application\Service\BlockVisibilityServiceInterface;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class BlockVisibilityService implements BlockVisibilityServiceInterface
{
    public function accountIsVisible(?string $viewerAccountIdentifier, string $targetAccountIdentifier): bool
    {
        return $viewerAccountIdentifier === null
            || ! BlockVisibility::exists($viewerAccountIdentifier, $targetAccountIdentifier);
    }

    public function routineIsVisible(?string $viewerAccountIdentifier, string $routineIdentifier): bool
    {
        if ($viewerAccountIdentifier === null) {
            return true;
        }

        $routineAuthorIdentifier = DB::table('routines')
            ->where('routine_identifier', $routineIdentifier)
            ->value('account_identifier');

        return $routineAuthorIdentifier === null
            || ! BlockVisibility::exists($viewerAccountIdentifier, (string) $routineAuthorIdentifier);
    }
}
