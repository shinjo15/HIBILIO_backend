<?php

declare(strict_types=1);

namespace Src\Account\Domain\Repository;

use Src\Account\Domain\Entity\Block;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

interface BlockRepositoryInterface
{
    public function find(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): ?Block;

    public function save(Block $block): void;

    public function delete(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): void;
}
