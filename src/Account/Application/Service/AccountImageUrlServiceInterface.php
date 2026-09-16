<?php

declare(strict_types=1);

namespace Src\Account\Application\Service;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface AccountImageUrlServiceInterface
{
    public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string;

    public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string;
}
