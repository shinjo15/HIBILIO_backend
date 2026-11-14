<?php

declare(strict_types=1);

namespace Src\Account\Domain\Entity;

use Src\Account\Domain\ValueObject\ListeningContentIdentifier;
use Src\Account\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ListeningContent
{
    public function __construct(
        private ListeningContentIdentifier $identifier,
        private AccountIdentifier $accountIdentifier,
        private ListeningContentUrl $url,
    ) {}

    public function identifier(): ListeningContentIdentifier
    {
        return $this->identifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function url(): ListeningContentUrl
    {
        return $this->url;
    }
}
