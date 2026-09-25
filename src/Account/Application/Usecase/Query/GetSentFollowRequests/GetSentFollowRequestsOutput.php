<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetSentFollowRequests;

final readonly class GetSentFollowRequestsOutput
{
    public function __construct(
        private string $accountIdentifier,
        private string $accountName,
        private ?string $accountBio,
        private ?string $iconImageUrl,
        private ?string $headerImageUrl,
    ) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function accountName(): string
    {
        return $this->accountName;
    }

    public function accountBio(): ?string
    {
        return $this->accountBio;
    }

    public function iconImageUrl(): ?string
    {
        return $this->iconImageUrl;
    }

    public function headerImageUrl(): ?string
    {
        return $this->headerImageUrl;
    }
}
