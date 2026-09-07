<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Repository;

use App\Models\SupportModel;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Domain\Entity\Support;
use Src\Support\Domain\Repository\SupportRepositoryInterface;

final class SupportRepository implements SupportRepositoryInterface
{
    public function exists(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): bool
    {
        return SupportModel::query()->where('account_identifier', $accountIdentifier->value())->where('post_identifier', $postIdentifier->value())->exists();
    }

    public function delete(AccountIdentifier $accountIdentifier, PostIdentifier $postIdentifier): void
    {
        SupportModel::query()->where('account_identifier', $accountIdentifier->value())->where('post_identifier', $postIdentifier->value())->delete();
    }

    public function save(Support $support): void
    {
        SupportModel::query()->create(['account_identifier' => $support->accountIdentifier()->value(), 'post_identifier' => $support->postIdentifier()->value()]);
    }
}
