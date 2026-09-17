<?php

declare(strict_types=1);

namespace Src\Shared\Application\Service;

interface BlockVisibilityServiceInterface
{
    public function accountIsVisible(?string $viewerAccountIdentifier, string $targetAccountIdentifier): bool;

    public function routineIsVisible(?string $viewerAccountIdentifier, string $routineIdentifier): bool;
}
