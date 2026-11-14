<?php

declare(strict_types=1);

namespace Src\ListeningContent\Domain\Factory;

use Src\ListeningContent\Domain\Entity\ListeningContent;
use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface ListeningContentFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, ListeningContentUrl $url): ListeningContent;
}
