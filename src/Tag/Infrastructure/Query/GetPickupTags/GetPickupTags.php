<?php

declare(strict_types=1);

namespace Src\Tag\Infrastructure\Query\GetPickupTags;

use Illuminate\Support\Facades\DB;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsInterface;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsOutput;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsOutputPort;

final class GetPickupTags implements GetPickupTagsInterface
{
    public function execute(): GetPickupTagsOutputPort
    {
        $tags = DB::table('tags')
            ->where('pickup', true)
            ->orderBy('tag_name')
            ->orderBy('tag_identifier')
            ->get(['tag_identifier', 'tag_name'])
            ->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
            ])
            ->all();

        return new GetPickupTagsOutput($tags);
    }
}
