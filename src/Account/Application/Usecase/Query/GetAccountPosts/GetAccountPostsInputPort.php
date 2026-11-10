<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountPosts;

interface GetAccountPostsInputPort
{
    public function accountIdentifier(): string;

    public function viewerAccountIdentifier(): ?string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
