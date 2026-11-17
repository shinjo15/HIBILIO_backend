<?php

declare(strict_types=1);

namespace Src\Admin\Domain\Entity;

use Src\Admin\Domain\ValueObject\AdminIdentifier;
use Src\Admin\Domain\ValueObject\AdminName;
use Src\Admin\Domain\ValueObject\EmailAddress;

final readonly class Admin
{
    public function __construct(
        private AdminIdentifier $identifier,
        private AdminName $name,
        private EmailAddress $emailAddress,
    ) {}

    public function identifier(): AdminIdentifier
    {
        return $this->identifier;
    }

    public function name(): AdminName
    {
        return $this->name;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }
}
