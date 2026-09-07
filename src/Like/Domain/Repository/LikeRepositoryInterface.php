<?php

declare(strict_types=1);

namespace Src\Like\Domain\Repository;

use Src\Like\Domain\Entity\Like;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

interface LikeRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): bool;

    public function delete(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): void;

    public function save(Like $like): void;
}
