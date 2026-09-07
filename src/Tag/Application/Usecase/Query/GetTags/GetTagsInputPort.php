<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetTags;

interface GetTagsInputPort
{
    public function tagName(): ?string;
}
