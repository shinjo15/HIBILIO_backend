<?php

declare(strict_types=1);

namespace App\Http\Actions\Tag;

use Illuminate\Http\JsonResponse;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsInterface;

final readonly class GetPickupTagsAction
{
    public function __construct(
        private GetPickupTagsInterface $getPickupTags,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->getPickupTags->execute();

        return new JsonResponse([
            'tags' => array_map(static fn (array $tag): array => [
                'tag_identifier' => $tag['tagIdentifier'],
                'tag_name' => $tag['tagName'],
            ], $result->tags()),
        ]);
    }
}
