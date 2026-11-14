<?php

declare(strict_types=1);

namespace Src\Account\Domain\Factory;

use Src\Account\Domain\Entity\ListeningContent;
use Src\Account\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ListeningContentFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, ListeningContentUrl $url): ListeningContent;
}
