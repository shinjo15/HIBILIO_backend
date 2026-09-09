<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAccountDetailsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_anonymous_public_account_details_with_only_the_response_contract_fields(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        $this->insertAccount($accountIdentifier, true, 'active', '公開アカウント', null);

        $this->getJson("/api/accounts/{$accountIdentifier}")
            ->assertOk()
            ->assertExactJson([
                'account_identifier' => $accountIdentifier,
                'account_name' => '公開アカウント',
                'account_bio' => null,
                'favorite_tags' => [],
                'social_links' => [],
            ]);
    }

    public function test_returns_not_found_for_an_unavailable_or_non_active_account(): void
    {
        $unavailableAccountIdentifier = '22222222-2222-4222-8222-222222222222';
        $temporarilyBannedAccountIdentifier = '33333333-3333-4333-8333-333333333333';
        $permanentlyBannedAccountIdentifier = '44444444-4444-4444-8444-444444444444';
        $this->insertAccount($unavailableAccountIdentifier, false, 'active', '非公開', null);
        $this->insertAccount($temporarilyBannedAccountIdentifier, true, 'temporarily_banned', '一時停止', null);
        $this->insertAccount($permanentlyBannedAccountIdentifier, true, 'permanently_banned', '永久停止', null);

        $this->getJson("/api/accounts/{$unavailableAccountIdentifier}")->assertNotFound();
        $this->getJson("/api/accounts/{$temporarilyBannedAccountIdentifier}")->assertNotFound();
        $this->getJson("/api/accounts/{$permanentlyBannedAccountIdentifier}")->assertNotFound();
    }

    private function insertAccount(string $identifier, bool $available, string $status, string $name, ?string $bio): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => $bio,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
