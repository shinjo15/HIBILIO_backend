<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetMyPosts;

interface GetMyPostsInterface
{
    public function execute(GetMyPostsInputPort $input): GetMyPostsOutputPort;
}
