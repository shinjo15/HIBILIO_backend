<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFavoriteTagPosts;

interface GetFavoriteTagPostsInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
