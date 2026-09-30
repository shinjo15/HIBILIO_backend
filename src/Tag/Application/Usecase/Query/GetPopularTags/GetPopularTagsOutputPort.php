<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetPopularTags;

interface GetPopularTagsOutputPort
{
    /** @return list<array{tagIdentifier: string, tagName: string, routineCount: int}> */
    public function tags(): array;
}
