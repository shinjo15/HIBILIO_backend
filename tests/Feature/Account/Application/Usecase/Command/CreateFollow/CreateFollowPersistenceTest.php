<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Application\Usecase\Command\CreateFollow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInput;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInterface;
use Src\Account\Domain\Exception\DuplicateFollowException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CreateFollowPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_a_follow_between_existing_accounts(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'following@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'followed@example.com');

        $this->createFollow(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );

        $this->assertDatabaseHas('follows', [
            'following_account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'followed_account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        ]);
    }

    public function test_allows_follows_in_both_directions(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'first@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'second@example.com');

        $this->createFollow(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );
        $this->createFollow(
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
        );

        $this->assertDatabaseCount('follows', 2);
    }

    public function test_rejects_a_duplicate_follow_without_creating_another_record(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'following@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'followed@example.com');
        $this->createFollow(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );

        $this->expectException(DuplicateFollowException::class);

        try {
            $this->createFollow(
                '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
                'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            );
        } finally {
            $this->assertDatabaseCount('follows', 1);
        }
    }

    private function createFollow(string $followingAccountIdentifier, string $followedAccountIdentifier): void
    {
        $this->app->make(CreateFollowInterface::class)->execute(new CreateFollowInput(
            followingAccountIdentifier: new AccountIdentifier($followingAccountIdentifier),
            followedAccountIdentifier: new AccountIdentifier($followedAccountIdentifier),
        ));
    }

    private function insertAccount(string $identifier, string $emailAddress): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $emailAddress,
            'email_address' => $emailAddress,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
