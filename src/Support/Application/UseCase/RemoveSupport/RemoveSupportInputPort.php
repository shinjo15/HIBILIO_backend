<?php

declare(strict_types=1);

namespace Src\Support\Application\UseCase\RemoveSupport;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

interface RemoveSupportInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function postIdentifier(): PostIdentifier;
}
