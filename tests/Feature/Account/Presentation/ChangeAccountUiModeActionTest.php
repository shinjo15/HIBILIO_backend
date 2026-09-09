<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class ChangeAccountUiModeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_the_authenticated_accounts_ui_mode(): void
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
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return '11111111-1111-4111-8111-111111111111';
            }
        });

        $this->patchJson('/api/my/account/ui-mode', ['ui_mode' => 'dark'])->assertNoContent();

        $this->assertDatabaseHas('accounts', ['account_identifier' => '11111111-1111-4111-8111-111111111111', 'ui_mode' => 'dark']);
    }

    public function test_rejects_an_unknown_ui_mode(): void
    {
        $this->patchJson('/api/my/account/ui-mode', ['ui_mode' => 'sepia'])->assertUnprocessable();
    }
}
