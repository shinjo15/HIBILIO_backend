<?php

declare(strict_types=1);

namespace Src\Like\Infrastructure\Query\GetMyLikes;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInputPort;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInterface;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesOutput;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesOutputPort;

final class GetMyLikes implements GetMyLikesInterface
{
    public function execute(GetMyLikesInputPort $input): GetMyLikesOutputPort
    {
        $paginator = DB::table('likes')
            ->join('posts', 'likes.post_identifier', '=', 'posts.post_identifier')
            ->where('likes.account_identifier', $input->accountIdentifier())
            ->where('posts.post_category', 'routine')
            ->where('posts.available', true)
            ->orderByDesc('likes.created_at')
            ->orderBy('likes.post_identifier')
            ->paginate($input->numberOfItemsPerPage(), [
                'posts.post_identifier',
                'posts.routine_identifier',
                'posts.post_category',
                'posts.post_like_count',
                'posts.post_support_count',
                'likes.created_at as liked_at',
            ], 'page', $input->page());

        $items = $paginator->getCollection()
            ->map(static fn (object $record): array => [
                'postIdentifier' => (string) $record->post_identifier,
                'routineIdentifier' => (string) $record->routine_identifier,
                'postCategory' => (string) $record->post_category,
                'postLikeCount' => (int) $record->post_like_count,
                'postSupportCount' => (int) $record->post_support_count,
                'likedAt' => (new DateTimeImmutable((string) $record->liked_at))->format(DATE_ATOM),
            ])
            ->values()
            ->all();

        return new GetMyLikesOutput($items, $paginator->total());
    }
}
