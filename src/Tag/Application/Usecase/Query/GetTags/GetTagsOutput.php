<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetTags;

final readonly class GetTagsOutput implements GetTagsOutputPort
{
    /** @param list<array{tagIdentifier: string, tagName: string}> $tags */
    public function __construct(
        private array $tags,
    ) {}

    public function tags(): array
    {
        return $this->tags;
    }
}
