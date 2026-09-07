<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetPopularRoutinePosts;

final readonly class GetPopularRoutinePostsInput implements GetPopularRoutinePostsInputPort
{
    public function __construct(
        private ?string $accountIdentifier,
        private int $page,
        private int $numberOfItemsPerPage,
    ) {}

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function numberOfItemsPerPage(): int
    {
        return $this->numberOfItemsPerPage;
    }

    public function page(): int
    {
        return $this->page;
    }
}
