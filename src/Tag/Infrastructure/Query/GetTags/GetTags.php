<?php

declare(strict_types=1);

namespace Src\Tag\Infrastructure\Query\GetTags;

use Illuminate\Support\Facades\DB;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsInputPort;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsInterface;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsOutput;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsOutputPort;

final class GetTags implements GetTagsInterface
{
    public function execute(GetTagsInputPort $input): GetTagsOutputPort
    {
        $tags = DB::table('tags')
            ->where('available', true)
            ->when(
                $input->tagName() !== null,
                static fn ($query) => $query->whereRaw(
                    "tag_name LIKE ? ESCAPE '!'",
                    [strtr($input->tagName(), ['!' => '!!', '%' => '!%', '_' => '!_']).'%'],
                ),
            )
            ->orderBy('tag_name')
            ->orderBy('tag_identifier')
            ->get(['tag_identifier', 'tag_name'])
            ->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
            ])
            ->all();

        return new GetTagsOutput($tags);
    }
}
