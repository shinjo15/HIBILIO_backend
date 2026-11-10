<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAccountRoutinesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_malformed_account_identifier_returns_not_found(): void
    {
        $this->getJson('/api/accounts/not-a-uuid/routines')->assertNotFound();
    }

    public function test_public_unposted_routine_is_visible_to_anonymous_viewers(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true, 'created_at' => '2026-09-01 10:00:00']);

        $this->getJson("/api/accounts/{$owner}/routines")->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $routine)->assertJsonPath('items.0.post_identifier', null);
        $this->getJson("/api/routines/{$routine}")->assertOk()->assertJsonPath('routine_name', 'Routine');
        $this->getJson('/api/routines/search?title=Routine')->assertOk()->assertJsonPath('items.0.routine_identifier', $routine)
            ->assertJsonPath('items.0.post_identifier', null);
    }

    public function test_matches_my_routines_for_self_and_preserves_routine_pagination(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $viewer = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active'],
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active'],
        ]);
        foreach (range(1, 3) as $n) {
            $routine = sprintf('30000000-0000-4000-8000-%012d', $n);
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine '.$n, 'available' => $n !== 3, 'created_at' => "2026-09-0{$n} 10:00:00"]);
        }
        DB::table('posts')->insert(['post_identifier' => '44444444-4444-4444-8444-444444444444', 'routine_identifier' => '30000000-0000-4000-8000-000000000001', 'post_category' => 'routine', 'post_like_count' => 7, 'post_support_count' => 11, 'available' => true, 'created_at' => '2026-09-01 10:00:00']);
        DB::table('likes')->insert(['account_identifier' => $viewer, 'post_identifier' => '44444444-4444-4444-8444-444444444444']);
        DB::table('tags')->insert(['tag_identifier' => '55555555-5555-4555-8555-555555555555', 'tag_name' => 'Tag', 'available' => true]);
        DB::table('routine_tags')->insert(['routine_identifier' => '30000000-0000-4000-8000-000000000001', 'tag_identifier' => '55555555-5555-4555-8555-555555555555', 'available' => true]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => '30000000-0000-4000-8000-000000000001', 'action_name' => 'Action', 'action_minutes' => 5, 'available' => true]);
        foreach ([$owner, $viewer] as $account) {
            foreach ([1, 2, 3] as $page) {
                $params = "?page={$page}&number_of_items_per_page=1";
                $actual = $this->withSession(['account_identifier' => $account])->getJson("/api/accounts/{$owner}/routines{$params}")->assertOk()->assertJsonPath('total', 2)->json();

                self::assertCount($page === 3 ? 0 : 1, $actual['items']);
                if ($page !== 3) {
                    $expected = $page === 1
                        ? ['routine_identifier' => '30000000-0000-4000-8000-000000000002', 'post_identifier' => null, 'post_like_count' => null, 'post_support_count' => null, 'liked' => null]
                        : ['routine_identifier' => '30000000-0000-4000-8000-000000000001', 'post_identifier' => '44444444-4444-4444-8444-444444444444', 'post_like_count' => 7, 'post_support_count' => 11, 'liked' => $account === $viewer];
                    foreach ($expected as $field => $value) {
                        self::assertArrayHasKey($field, $actual['items'][0]);
                        self::assertSame($value, $actual['items'][0][$field]);
                    }
                }
                if ($account === $owner) {
                    self::assertSame($actual, $this->withSession(['account_identifier' => $owner])->getJson("/api/my/routines{$params}")->assertOk()->json());
                }
            }
        }
        $this->withSession(['account_identifier' => $viewer])->getJson("/api/accounts/{$owner}/routines?page=2&number_of_items_per_page=1")
            ->assertOk()->assertJsonPath('items.0.liked', true)->assertJsonPath('items.0.tags.0.tag_name', 'Tag')
            ->assertJsonPath('items.0.routine_actions.0.action_name', 'Action');
    }

    public function test_private_account_requires_follow_and_both_block_directions_are_hidden(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $viewer = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'private', 'available' => true, 'status' => 'active'],
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active'],
        ]);
        DB::table('routines')->insert(['routine_identifier' => '33333333-3333-4333-8333-333333333333', 'account_identifier' => $owner, 'routine_name' => 'Unposted', 'available' => true]);
        $url = "/api/accounts/{$owner}/routines";
        $this->getJson($url)->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        $this->withSession(['account_identifier' => $owner])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $owner]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.post_identifier', null);
        foreach ([[$owner, $viewer], [$viewer, $owner]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
            DB::table('blocks')->delete();
        }
    }

    public function test_unavailable_inactive_and_missing_accounts_return_empty_lists(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $missing = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert(['routine_identifier' => '33333333-3333-4333-8333-333333333333', 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        $this->getJson("/api/accounts/{$missing}/routines")->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        foreach ([['available' => false], ['available' => true, 'status' => 'suspended']] as $state) {
            DB::table('accounts')->where('account_identifier', $owner)->update($state);
            $this->getJson("/api/accounts/{$owner}/routines")->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        }
    }
}
