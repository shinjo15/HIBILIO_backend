<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\RemoveLike;

interface RemoveLikeInterface
{
    public function execute(RemoveLikeInputPort $input): void;
}
