<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Service;

use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RoutineExecutionAccountIconUrl
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function forAccount(string $accountIdentifier): ?string
    {
        return $this->accountImageUrlService->iconImageUrl(new AccountIdentifier($accountIdentifier));
    }
}
