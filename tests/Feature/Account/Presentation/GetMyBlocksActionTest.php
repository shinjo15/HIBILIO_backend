<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetMyBlocksActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_accounts_blocked_by_the_authenticated_account(): void
    {
        $this->account('11111111-1111-4111-8111-111111111111', 'ブロックした人');
        $this->account('22222222-2222-4222-8222-222222222222', 'ブロック対象', 'ブロック対象の説明');
        $this->account('33333333-3333-4333-8333-333333333333', '他の対象');
        DB::table('blocks')->insert([
            ['blocking_account_identifier' => '11111111-1111-4111-8111-111111111111', 'blocked_account_identifier' => '22222222-2222-4222-8222-222222222222', 'created_at' => now(), 'updated_at' => now()],
            ['blocking_account_identifier' => '33333333-3333-4333-8333-333333333333', 'blocked_account_identifier' => '22222222-2222-4222-8222-222222222222', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/my/blocks')
            ->assertOk()
            ->assertExactJson(['blocks' => [['account_identifier' => '22222222-2222-4222-8222-222222222222', 'account_name' => 'ブロック対象', 'account_bio' => 'ブロック対象の説明']]]);
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->getJson('/api/my/blocks')->assertUnauthorized();
    }

    private function account(string $identifier, string $name, ?string $bio = null): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'account_bio' => $bio, 'email_address' => "$identifier@example.com", 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }
}
