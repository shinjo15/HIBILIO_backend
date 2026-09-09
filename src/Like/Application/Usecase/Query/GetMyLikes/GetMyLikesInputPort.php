<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetMyLikes;

interface GetMyLikesInputPort
{
    public function accountIdentifier(): string;

    public function page(): int;

    public function numberOfItemsPerPage(): int;
}
