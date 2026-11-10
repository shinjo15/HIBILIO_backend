<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountPosts;

final readonly class GetAccountPostsInput implements GetAccountPostsInputPort
{
    public function __construct(private string $accountIdentifier, private int $page, private int $numberOfItemsPerPage, private ?string $viewerAccountIdentifier) {}

    public function viewerAccountIdentifier(): ?string
    {
        return $this->viewerAccountIdentifier;
    }

    public function accountIdentifier(): string
    {
        return $this->accountIdentifier;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }
}
