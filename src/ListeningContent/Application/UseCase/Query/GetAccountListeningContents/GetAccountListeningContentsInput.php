<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents;

final readonly class GetAccountListeningContentsInput implements GetAccountListeningContentsInputPort
{
    public function __construct(private string $accountIdentifier, private ?string $viewerAccountIdentifier) {}

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
    }
}
