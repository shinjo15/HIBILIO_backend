<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetFavoriteTagPosts;

interface GetFavoriteTagPostsInterface
{
    public function execute(GetFavoriteTagPostsInputPort $input): GetFavoriteTagPostsOutputPort;
}
