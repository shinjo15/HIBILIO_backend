<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Application\Usecase\Command\CreateBlock;

use PHPUnit\Framework\TestCase;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlock;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInput;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Entity\Block;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateBlockException;
use Src\Account\Domain\Exception\SelfBlockException;
use Src\Account\Domain\Factory\BlockFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateBlockTest extends TestCase
{
    public function test_creates_a_block_when_the_blocked_account_exists(): void
    {
        $repository = new InMemoryBlockRepository;

        $this->useCase(true, $repository)->execute($this->input());

        self::assertCount(1, $repository->saved);
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $repository->saved[0]->blockingAccountIdentifier()->value());
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $repository->saved[0]->blockedAccountIdentifier()->value());
    }

    public function test_rejects_a_missing_blocked_account(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $this->useCase(false, new InMemoryBlockRepository)->execute($this->input());
    }

    public function test_rejects_a_duplicate_block_in_the_same_direction(): void
    {
        $repository = new InMemoryBlockRepository;
        $repository->existing = $this->block();

        $this->expectException(DuplicateBlockException::class);

        $this->useCase(true, $repository)->execute($this->input());
    }

    public function test_allows_blocking_in_the_opposite_direction(): void
    {
        $repository = new InMemoryBlockRepository;
        $repository->existing = (new TestBlockFactory)->create(
            blockingAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            blockedAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
        );

        $this->useCase(true, $repository)->execute($this->input());

        self::assertCount(1, $repository->saved);
    }

    public function test_rejects_blocking_oneself(): void
    {
        $this->expectException(SelfBlockException::class);

        $this->useCase(true, new InMemoryBlockRepository)->execute(
            new CreateBlockInput(
                blockingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
                blockedAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            ),
        );
    }

    private function useCase(bool $blockedAccountExists, InMemoryBlockRepository $blockRepository): CreateBlock
    {
        return new CreateBlock(
            new ImmediateTransactionManager,
            new InMemoryAccountRepository($blockedAccountExists),
            $blockRepository,
            new TestBlockFactory,
        );
    }

    private function input(): CreateBlockInput
    {
        return new CreateBlockInput(
            blockingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            blockedAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
        );
    }

    private function block(): Block
    {
        return (new TestBlockFactory)->create(
            blockingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            blockedAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
        );
    }
}

final class ImmediateTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    public function __construct(private bool $exists) {}

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        if (! $this->exists) {
            return null;
        }

        return Account::create(
            $accountIdentifier,
            new AccountName('対象アカウント'),
            null,
            new EmailAddress('target@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
        );
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return null;
    }

    public function save(Account $account): void {}

    public function updateProfile(Account $account): void {}
}

final class InMemoryBlockRepository implements BlockRepositoryInterface
{
    public ?Block $existing = null;

    /** @var list<Block> */
    public array $saved = [];

    public function find(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): ?Block {
        if ($this->existing === null) {
            return null;
        }

        if (
            $this->existing->blockingAccountIdentifier()->value() !== $blockingAccountIdentifier->value()
            || $this->existing->blockedAccountIdentifier()->value() !== $blockedAccountIdentifier->value()
        ) {
            return null;
        }

        return $this->existing;
    }

    public function save(Block $block): void
    {
        $this->saved[] = $block;
    }

    public function delete(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): void {}
}

final class TestBlockFactory implements BlockFactoryInterface
{
    public function create(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): Block {
        return new Block(
            blockingAccountIdentifier: $blockingAccountIdentifier,
            blockedAccountIdentifier: $blockedAccountIdentifier,
        );
    }
}
