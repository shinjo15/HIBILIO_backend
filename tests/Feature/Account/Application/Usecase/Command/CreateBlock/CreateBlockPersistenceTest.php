<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Application\Usecase\Command\CreateBlock;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInput;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInterface;
use Src\Account\Domain\Exception\DuplicateBlockException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CreateBlockPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_persists_a_block_between_existing_accounts(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'blocking@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'blocked@example.com');

        $this->createBlock(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );

        $this->assertDatabaseHas('blocks', [
            'blocking_account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'blocked_account_identifier' => 'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        ]);
    }

    public function test_allows_blocks_in_both_directions(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'first@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'second@example.com');

        $this->createBlock(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );
        $this->createBlock(
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
        );

        $this->assertDatabaseCount('blocks', 2);
    }

    public function test_rejects_a_duplicate_block_without_creating_another_record(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'blocking@example.com');
        $this->insertAccount('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', 'blocked@example.com');
        $this->createBlock(
            '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
        );

        $this->expectException(DuplicateBlockException::class);

        try {
            $this->createBlock(
                '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
                'f0cfa1a3-1ac7-44af-9bf4-b36c9262f028',
            );
        } finally {
            $this->assertDatabaseCount('blocks', 1);
        }
    }

    private function createBlock(string $blockingAccountIdentifier, string $blockedAccountIdentifier): void
    {
        $this->app->make(CreateBlockInterface::class)->execute(new CreateBlockInput(
            blockingAccountIdentifier: new AccountIdentifier($blockingAccountIdentifier),
            blockedAccountIdentifier: new AccountIdentifier($blockedAccountIdentifier),
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
