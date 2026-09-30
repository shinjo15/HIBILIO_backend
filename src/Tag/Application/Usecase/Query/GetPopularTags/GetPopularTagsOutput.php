<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetPopularTags;

final readonly class GetPopularTagsOutput implements GetPopularTagsOutputPort
{
    /** @param list<array{tagIdentifier: string, tagName: string, routineCount: int}> $tags */
    public function __construct(
        private array $tags,
    ) {}

    public function tags(): array
    {
        return $this->tags;
    }
}
