<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Entity;

use Src\Admin\Domain\ValueObject\PermissionGroupIdentifier;
use Src\Admin\Domain\ValueObject\PermissionGroupName;

final readonly class PermissionGroup
{
    public function __construct(
        private PermissionGroupIdentifier $identifier,
        private PermissionGroupName $name,
    ) {}

    public function identifier(): PermissionGroupIdentifier
    {
        return $this->identifier;
    }

    public function name(): PermissionGroupName
    {
        return $this->name;
    }
}
