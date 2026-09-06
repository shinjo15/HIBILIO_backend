<?php

declare(strict_types=1);

namespace Src\Post\Infrastructure\Repository;

use App\Models\PostModel;
use Src\Post\Domain\Entity\Post;
use Src\Post\Domain\Repository\PostRepositoryInterface;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class PostRepository implements PostRepositoryInterface
{
    public function find(PostIdentifier $postIdentifier): ?Post
    {
        $model = PostModel::query()->where('post_identifier', $postIdentifier->value())->lockForUpdate()->first();
        if ($model === null) {
            return null;
        }

        return Post::create(
            new PostIdentifier($model->post_identifier),
            new RoutineIdentifier($model->routine_identifier),
            $model->routine_execution_identifier === null
                ? null
                : new RoutineExecutionIdentifier($model->routine_execution_identifier),
            PostCategory::from($model->post_category),
            new PostLikeCount($model->post_like_count),
            new PostSupportCount($model->post_support_count),
        );
    }

    public function save(Post $post): void
    {
        PostModel::query()->updateOrCreate(['post_identifier' => $post->postIdentifier()->value()], [
            'post_identifier' => $post->postIdentifier()->value(),
            'routine_identifier' => $post->routineIdentifier()->value(),
            'routine_execution_identifier' => $post->routineExecutionIdentifier()?->value(),
            'post_category' => $post->postCategory()->value,
            'post_like_count' => $post->postLikeCount()->value(),
            'post_support_count' => $post->postSupportCount()->value(),
            'available' => true,
        ]);
    }
}
