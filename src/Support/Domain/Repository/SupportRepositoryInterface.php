<?php

declare(strict_types=1);

namespace Src\Support\Domain\Repository;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Domain\Entity\Support;

interface SupportRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): bool;

    public function delete(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): void;

    public function save(Support $support): void;
}
