<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Application\UseCase\CreateSupport;

use App\Models\AccountModel;
use App\Models\PostModel;
use App\Models\RoutineExecutionModel;
use App\Models\RoutineModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Application\UseCase\CreateSupport\CreateSupportInput;
use Src\Support\Application\UseCase\CreateSupport\CreateSupportInterface;
use Src\Support\Domain\Exception\AlreadySupportedException;
use Tests\TestCase;

final class CreateSupportPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_support_and_increments_the_action_post_support_count(): void
    {
        $this->createActionPost();

        $this->app->make(CreateSupportInterface::class)->execute($this->input());

        $this->assertDatabaseHas('supports', [
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f',
        ]);
        $this->assertDatabaseHas('posts', [
            'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f',
            'post_support_count' => 1,
        ]);
    }

    public function test_rejects_duplicate_support_without_incrementing_the_count(): void
    {
        $this->createActionPost();
        $useCase = $this->app->make(CreateSupportInterface::class);
        $useCase->execute($this->input());

        $this->expectException(AlreadySupportedException::class);
        try {
            $useCase->execute($this->input());
        } finally {
            $this->assertDatabaseCount('supports', 1);
            $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_support_count' => 1]);
        }
    }

    private function input(): CreateSupportInput
    {
        return new CreateSupportInput(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new PostIdentifier('e1954b83-b532-40ae-8b9e-49d488040d0f'));
    }

    private function createActionPost(): void
    {
        AccountModel::query()->create([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'account_name' => '実行者',
            'email_address' => 'executor@hibilio.local',
            'available' => true,
            'status' => 'active',
        ]);
        RoutineModel::query()->create(['routine_identifier' => '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', 'routine_name' => '朝活', 'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'routine_execution_minutes' => 1, 'available' => true]);
        RoutineExecutionModel::query()->create([
            'routine_execution_identifier' => '64b8d590-07cb-49ca-bfd9-cb9f40e26bd3',
            'executor_account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'routine_identifier' => '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3',
            'executed_at' => now(),
        ]);
        PostModel::query()->create(['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'routine_identifier' => '34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', 'routine_execution_identifier' => '64b8d590-07cb-49ca-bfd9-cb9f40e26bd3', 'post_category' => 'action', 'post_like_count' => 0, 'post_support_count' => 0, 'available' => true]);
    }
}
