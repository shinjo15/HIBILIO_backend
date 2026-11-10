<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountPosts;

interface GetAccountPostsOutputPort
{
    /** @return list<array<string, mixed>> */
    public function items(): array;

    public function total(): int;
}
