<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class AccountIdentity
{
    public function __construct(
        public string $accountIdentifier,
        public string $name,
    ) {}
}
