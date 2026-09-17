<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class GetAccountDetailsInput implements GetAccountDetailsInputPort
{
    public function __construct(
        private string $accountIdentifier,
        private ?string $viewerAccountIdentifier = null,
    ) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
    }
}
