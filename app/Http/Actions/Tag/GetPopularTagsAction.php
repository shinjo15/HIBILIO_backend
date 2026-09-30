<?php

declare(strict_types=1);

namespace App\Http\Actions\Tag;

use Illuminate\Http\JsonResponse;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsInput;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsInterface;

final readonly class GetPopularTagsAction
{
    public function __construct(
        private GetPopularTagsInterface $getPopularTags,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->getPopularTags->execute(new GetPopularTagsInput);

        return new JsonResponse([
            'tags' => array_map(static fn (array $tag): array => [
                'tag_identifier' => $tag['tagIdentifier'],
                'tag_name' => $tag['tagName'],
                'routine_count' => $tag['routineCount'],
            ], $result->tags()),
        ]);
    }
}
