<?php

declare(strict_types=1);

namespace App\Http\Actions\Tag;

use App\Http\Requests\Tag\GetTagsRequest;
use Illuminate\Http\JsonResponse;
use Src\Tag\Application\Usecase\Query\GetTags\GetTagsInterface;

final readonly class GetTagsAction
{
    public function __construct(
        private GetTagsInterface $getTags,
    ) {}

    public function __invoke(GetTagsRequest $request): JsonResponse
    {
        $result = $this->getTags->execute($request->toInput());

        return new JsonResponse([
            'tags' => array_map(static fn (array $tag): array => [
                'tag_identifier' => $tag['tagIdentifier'],
                'tag_name' => $tag['tagName'],
            ], $result->tags()),
        ]);
    }
}
