<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RegisterListeningContent;

use Src\Account\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class RegisterListeningContentInput implements RegisterListeningContentInputPort
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
