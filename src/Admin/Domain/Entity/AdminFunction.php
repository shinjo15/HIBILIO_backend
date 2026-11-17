<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Entity;

use Src\Admin\Domain\ValueObject\AdminFunctionIdentifier;
use Src\Admin\Domain\ValueObject\AdminFunctionName;

final readonly class AdminFunction
{
    public function __construct(
        private AdminFunctionIdentifier $identifier,
        private AdminFunctionName $name,
    ) {}

    public function identifier(): AdminFunctionIdentifier
    {
        return $this->identifier;
    }

    public function name(): AdminFunctionName
    {
        return $this->name;
    }
}
