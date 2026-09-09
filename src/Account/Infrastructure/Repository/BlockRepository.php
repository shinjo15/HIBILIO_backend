<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Repository;

use App\Models\BlockModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Account\Domain\Entity\Block;
use Src\Account\Domain\Exception\DuplicateBlockException;
use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class BlockRepository implements BlockRepositoryInterface
{
    public function find(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): ?Block {
        $model = BlockModel::query()
            ->where('blocking_account_identifier', $blockingAccountIdentifier->value())
            ->where('blocked_account_identifier', $blockedAccountIdentifier->value())
            ->first();

        return $model === null ? null : $this->restore($model);
    }

    public function save(Block $block): void
    {
        try {
            BlockModel::query()->create([
                'blocking_account_identifier' => $block->blockingAccountIdentifier()->value(),
                'blocked_account_identifier' => $block->blockedAccountIdentifier()->value(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateBlockException;
        }
    }

    public function delete(
        AccountIdentifier $blockingAccountIdentifier,
        AccountIdentifier $blockedAccountIdentifier,
    ): void {
        BlockModel::query()
            ->where('blocking_account_identifier', $blockingAccountIdentifier->value())
            ->where('blocked_account_identifier', $blockedAccountIdentifier->value())
            ->delete();
    }

    private function restore(BlockModel $model): Block
    {
        return new Block(
            blockingAccountIdentifier: new AccountIdentifier($model->blocking_account_identifier),
            blockedAccountIdentifier: new AccountIdentifier($model->blocked_account_identifier),
        );
    }
}
