<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Application\UseCase\RemoveSupport;

use App\Models\AccountModel;
use App\Models\PostModel;
use App\Models\RoutineExecutionModel;
use App\Models\RoutineModel;
use App\Models\SupportModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Application\UseCase\RemoveSupport\RemoveSupportInput;
use Src\Support\Application\UseCase\RemoveSupport\RemoveSupportInterface;
use Src\Support\Domain\Exception\NotSupportedException;
use Tests\TestCase;

final class RemoveSupportPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_support_and_decrements_action_post_support_count(): void
    {
        $this->createActionPost();
        $this->createSupport('3b5581e9-16df-4879-b7d2-5d88dca6ab87');

        $this->app->make(RemoveSupportInterface::class)->execute($this->input());

        $this->assertDatabaseMissing('supports', ['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
        $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_support_count' => 0]);
    }

    public function test_rejects_removing_a_support_that_does_not_exist(): void
    {
        $this->createActionPost();

        $this->expectException(NotSupportedException::class);
        try {
            $this->app->make(RemoveSupportInterface::class)->execute($this->input());
        } finally {
            $this->assertDatabaseCount('supports', 0);
            $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_support_count' => 0]);
        }
    }

    public function test_rejects_removing_another_account_support(): void
    {
        $this->createActionPost();
        $this->createSupport('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee');

        $this->expectException(NotSupportedException::class);
        try {
            $this->app->make(RemoveSupportInterface::class)->execute($this->input());
        } finally {
            $this->assertDatabaseHas('supports', ['account_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
            $this->assertDatabaseHas('posts', ['post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f', 'post_support_count' => 1]);
        }
    }

    private function input(): RemoveSupportInput
    {
        return new RemoveSupportInput(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new PostIdentifier('e1954b83-b532-40ae-8b9e-49d488040d0f'));
    }

    private function createSupport(string $accountIdentifier): void
    {
        SupportModel::query()->create(['account_identifier' => $accountIdentifier, 'post_identifier' => 'e1954b83-b532-40ae-8b9e-49d488040d0f']);
        PostModel::query()->where('post_identifier', 'e1954b83-b532-40ae-8b9e-49d488040d0f')->update(['post_support_count' => 1]);
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
