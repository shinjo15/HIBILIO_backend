<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class ChangeAccountVisibilityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_the_authenticated_accounts_visibility_without_changing_its_status(): void
    {
        $this->insertAccount();
        $this->authenticateAsAccount();

        $this->patchJson('/api/my/account/visibility', ['visibility' => 'private'])
            ->assertNoContent();

        $this->assertDatabaseHas('accounts', [
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'visibility' => 'private',
            'status' => 'active',
        ]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->patchJson('/api/my/account/visibility', ['visibility' => 'private'])
            ->assertUnauthorized();
    }

    private function insertAccount(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'account_name' => '既存ユーザー',
            'email_address' => 'original@example.com',
            'available' => true,
            'status' => 'active',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authenticateAsAccount(): void
    {
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return '11111111-1111-4111-8111-111111111111';
            }
        });
    }
}
