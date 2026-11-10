<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyPosts;

final readonly class GetMyPostsInput implements GetMyPostsInputPort
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
