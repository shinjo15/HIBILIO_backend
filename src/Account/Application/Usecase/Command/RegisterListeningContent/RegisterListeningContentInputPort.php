<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RegisterListeningContent;

use Src\Account\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface RegisterListeningContentInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function url(): ListeningContentUrl;
}
