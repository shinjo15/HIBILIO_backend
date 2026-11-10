<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountPosts;

interface GetAccountPostsInterface
{
    public function execute(GetAccountPostsInputPort $input): GetAccountPostsOutputPort;
}
