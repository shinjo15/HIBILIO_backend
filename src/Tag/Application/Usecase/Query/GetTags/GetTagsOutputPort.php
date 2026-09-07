<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetTags;

interface GetTagsOutputPort
{
    /** @return list<array{tagIdentifier: string, tagName: string}> */
    public function tags(): array;
}
