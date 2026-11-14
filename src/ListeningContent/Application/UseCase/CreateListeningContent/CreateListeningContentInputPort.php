<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\CreateListeningContent;

use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface CreateListeningContentInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function url(): ListeningContentUrl;
}
