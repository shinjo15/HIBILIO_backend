<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetTags;

interface GetTagsInterface
{
    public function execute(GetTagsInputPort $input): GetTagsOutputPort;
}
