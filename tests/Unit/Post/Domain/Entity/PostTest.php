<?php

declare(strict_types=1);

namespace Tests\Unit\Post\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Post\Domain\Entity\Post;
use Src\Post\Domain\Exception\UnsupportedPostLikeException;
use Src\Post\Domain\Exception\UnsupportedPostSupportException;
use Src\Post\Domain\ValueObject\PostCategory;
use Src\Post\Domain\ValueObject\PostLikeCount;
use Src\Post\Domain\ValueObject\PostSupportCount;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class PostTest extends TestCase
{
    public function test_retains_the_values_of_a_created_routine_post(): void
    {
        $postIdentifier = new PostIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f');
        $routineIdentifier = new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3');
        $postLikeCount = new PostLikeCount(3);
        $postSupportCount = new PostSupportCount(0);

        $post = Post::create(
            postIdentifier: $postIdentifier,
            routineIdentifier: $routineIdentifier,
            routineExecutionIdentifier: null,
            postCategory: PostCategory::ROUTINE,
            postLikeCount: $postLikeCount,
            postSupportCount: $postSupportCount,
        );

        $this->assertSame($postIdentifier, $post->postIdentifier());
        $this->assertSame($routineIdentifier, $post->routineIdentifier());
        $this->assertNull($post->routineExecutionIdentifier());
        $this->assertSame(PostCategory::ROUTINE, $post->postCategory());
        $this->assertSame($postLikeCount, $post->postLikeCount());
        $this->assertSame($postSupportCount, $post->postSupportCount());
    }

    public function test_rejects_an_action_post_without_a_routine_execution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('実行投稿にはルーティン実行が必要です。');

        Post::create(
            new PostIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            null,
            PostCategory::ACTION,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }

    public function test_rejects_a_routine_post_with_a_routine_execution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ルーティン投稿にはルーティン実行を指定できません。');

        Post::create(
            new PostIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            new RoutineExecutionIdentifier('2fd8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            PostCategory::ROUTINE,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }

    public function test_rejects_a_negative_like_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('投稿のいいね総数は0以上である必要があります。');

        new PostLikeCount(-1);
    }

    public function test_rejects_a_negative_support_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('投稿の応援総数は0以上である必要があります。');

        new PostSupportCount(-1);
    }

    public function test_increments_support_count_for_an_action_post(): void
    {
        $post = $this->actionPost();

        $supportedPost = $post->incrementSupportCount();

        $this->assertSame(1, $supportedPost->postSupportCount()->value());
    }

    public function test_rejects_support_for_a_routine_post(): void
    {
        $this->expectException(UnsupportedPostSupportException::class);
        $this->expectExceptionMessage('応援できるのは実行投稿のみです。');

        $this->routinePost()->incrementSupportCount();
    }

    public function test_increments_like_count_for_a_routine_post(): void
    {
        $likedPost = $this->routinePost()->incrementLikeCount();

        $this->assertSame(1, $likedPost->postLikeCount()->value());
    }

    public function test_rejects_like_for_an_action_post(): void
    {
        $this->expectException(UnsupportedPostLikeException::class);

        $this->actionPost()->incrementLikeCount();
    }

    private function routinePost(): Post
    {
        return Post::create(
            new PostIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            null,
            PostCategory::ROUTINE,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }

    private function actionPost(): Post
    {
        return Post::create(
            new PostIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            new RoutineExecutionIdentifier('2fd8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            PostCategory::ACTION,
            new PostLikeCount(0),
            new PostSupportCount(0),
        );
    }
}
