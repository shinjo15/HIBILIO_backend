<?php

declare(strict_types=1);

namespace Src\Account\Application\Service;

use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface StorageServiceInterface
{
    public function uploadIcon(
        AccountIdentifier $accountIdentifier,
        AccountIcon $icon,
    ): void;

    public function uploadHeader(
        AccountIdentifier $accountIdentifier,
        AccountHeader $header,
    ): void;

    public function deleteIcon(AccountIdentifier $accountIdentifier): void;

    public function deleteHeader(AccountIdentifier $accountIdentifier): void;
}
