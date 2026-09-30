<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetPopularTags;

interface GetPopularTagsInterface
{
    public function execute(GetPopularTagsInputPort $input): GetPopularTagsOutputPort;
}
