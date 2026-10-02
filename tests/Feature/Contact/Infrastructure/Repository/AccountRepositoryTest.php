<?php

declare(strict_types=1);

namespace Tests\Feature\Contact\Infrastructure\Repository;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Contact\Infrastructure\Repository\AccountRepository;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_the_existing_accounts_email_address_value_object(): void
    {
        $this->insertAccount('3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account@example.com');

        $emailAddress = (new AccountRepository)->findEmailAddress(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'));

        self::assertSame('account@example.com', $emailAddress?->value());
    }

    public function test_returns_null_when_the_account_does_not_exist(): void
    {
        $emailAddress = (new AccountRepository)->findEmailAddress(new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'));

        self::assertNull($emailAddress);
    }

    private function insertAccount(string $identifier, string $emailAddress): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => 'お問い合わせユーザー',
            'email_address' => $emailAddress,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
