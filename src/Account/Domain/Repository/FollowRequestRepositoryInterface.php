<?php

declare(strict_types=1);

namespace Src\Account\Domain\Repository;

use Src\Account\Domain\Entity\FollowRequest;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface FollowRequestRepositoryInterface
{
    public function find(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest;

    public function findForUpdate(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest;

    public function save(FollowRequest $followRequest): void;

    public function delete(FollowRequest $followRequest): void;
}
