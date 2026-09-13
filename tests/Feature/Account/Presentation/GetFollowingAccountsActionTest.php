<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetFollowingAccountsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_authenticated_accounts_following_accounts(): void
    {
        $this->account('11111111-1111-4111-8111-111111111111', 'ログイン中');
        $this->account('22222222-2222-4222-8222-222222222222', 'フォロー対象', '説明');
        $this->follow('11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222');

        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/my/following')
            ->assertExactJson(['following_accounts' => [['account_identifier' => '22222222-2222-4222-8222-222222222222', 'account_name' => 'フォロー対象', 'account_bio' => '説明']]]);
    }

    public function test_returns_the_specified_accounts_following_accounts(): void
    {
        $this->account('11111111-1111-4111-8111-111111111111', '指定アカウント');
        $this->account('22222222-2222-4222-8222-222222222222', 'フォロー対象');
        $this->follow('11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222');

        $this->getJson('/api/accounts/11111111-1111-4111-8111-111111111111/following')
            ->assertExactJson(['following_accounts' => [['account_identifier' => '22222222-2222-4222-8222-222222222222', 'account_name' => 'フォロー対象', 'account_bio' => null]]]);
    }

    private function account(string $identifier, string $name, ?string $bio = null): void
    {
        DB::table('accounts')->insert(['account_identifier' => $identifier, 'account_name' => $name, 'account_bio' => $bio, 'email_address' => "$identifier@example.com", 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function follow(string $followingAccountIdentifier, string $followedAccountIdentifier): void
    {
        DB::table('follows')->insert(['following_account_identifier' => $followingAccountIdentifier, 'followed_account_identifier' => $followedAccountIdentifier, 'created_at' => now(), 'updated_at' => now()]);
    }
}
