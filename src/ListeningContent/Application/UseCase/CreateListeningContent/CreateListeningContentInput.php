<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\CreateListeningContent;

use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class CreateListeningContentInput implements CreateListeningContentInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private ListeningContentUrl $url) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function url(): ListeningContentUrl
    {
        return $this->url;
    }
}
