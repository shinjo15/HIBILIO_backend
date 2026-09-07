<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetTags;

final readonly class GetTagsInput implements GetTagsInputPort
{
    public function __construct(
        private ?string $tagName,
    ) {}

    public function tagName(): ?string
    {
        return $this->tagName;
    }
}
