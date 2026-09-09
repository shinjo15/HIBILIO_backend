<?php

declare(strict_types=1);

namespace Src\Like\Application\Usecase\Query\GetMyLikes;

interface GetMyLikesInterface
{
    public function execute(GetMyLikesInputPort $input): GetMyLikesOutputPort;
}
