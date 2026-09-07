<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Application\UseCase\RemoveLike;

use App\Models\LikeModel;
use App\Models\PostModel;
use App\Models\RoutineModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInput;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInterface;
use Src\Like\Domain\Exception\NotLikedException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Tests\TestCase;

final class RemoveLikePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_like_and_decrements_routine_post_like_count(): void
    {
        $this->createRoutinePost();
        $this->createLike('3b5581e9-16df-4879-b7d2-5d88dca6ab87');

        $this->app->make(RemoveLikeInterface::class)->execute($this->input());

        $this->assertDatabaseMissing('likes', ['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
        $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_like_count' => 0]);
    }

    public function test_rejects_removing_a_like_that_does_not_exist(): void
    {
        $this->createRoutinePost();

        $this->expectException(NotLikedException::class);
        try {
            $this->app->make(RemoveLikeInterface::class)->execute($this->input());
        } finally {
            $this->assertDatabaseCount('likes', 0);
            $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_like_count' => 0]);
        }
    }

    public function test_rejects_removing_another_account_like(): void
    {
        $this->createRoutinePost();
        $this->createLike('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee');

        $this->expectException(NotLikedException::class);
        try {
            $this->app->make(RemoveLikeInterface::class)->execute($this->input());
        } finally {
            $this->assertDatabaseHas('likes', ['account_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
            $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_like_count' => 1]);
        }
    }

    private function input(): RemoveLikeInput
    {
        return new RemoveLikeInput(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new PostIdentifier('e1954b83-b532-40ae-8b9e-49d488040d0f'));
    }

    private function createLike(string $accountIdentifier): void
    {
        LikeModel::query()->create(['account_identifier' => $accountIdentifier, 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
        PostModel::query()->where('post_identifier', 'e1954b83-b532-40ae-8b9e-49d488040d0f')->update(['post_like_count' => 1]);
    }

    private function createRoutinePost(): void
    {
        RoutineModel::query()->create(['routine_identifier' => '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', 'routine_name' => '朝活', 'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'routine_execution_minutes' => 1, 'available' => true]);
        PostModel::query()->create(['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'routine_identifier' => '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', 'post_category' => 'routine', 'post_like_count' => 0, 'post_support_count' => 0, 'available' => true]);
    }
}
