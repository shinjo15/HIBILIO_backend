<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Domain\Entity\Account;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AccountTest extends TestCase
{
    public function test_restored_account_awards_experience_for_each_execution(): void
    {
        $id = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $account = new Account($id, 2900);
        $account->recordRoutineExecution();
        self::assertSame($id, $account->accountIdentifier());
        self::assertSame(3000, $account->experience());
    }

    public function test_restore_rejects_negative_experience(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Account(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), -1);
    }
}
