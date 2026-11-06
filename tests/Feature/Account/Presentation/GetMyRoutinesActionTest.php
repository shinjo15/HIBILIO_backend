<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetMyRoutinesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_my_posts_route_is_removed(): void
    {
        $this->getJson('/api/my/posts')->assertNotFound();
    }

    public function test_returns_own_routine_without_a_post(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $account, 'routine_name' => 'Routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->withSession(['account_identifier' => $account])->getJson('/api/my/routines')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.routine_identifier', $routine)
            ->assertJsonPath('items.0.post_identifier', null)->assertJsonPath('items.0.posted_at', null)
            ->assertJsonPath('items.0.post_like_count', null)->assertJsonPath('items.0.post_support_count', null)
            ->assertJsonPath('items.0.liked', null);
    }

    public function test_unposted_routine_counts_only_available_customizations_for_both_routes(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $parent = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert([
            ['routine_identifier' => $parent, 'parent_routine_identifier' => null, 'account_identifier' => $owner, 'routine_name' => 'Parent', 'available' => true],
            ['routine_identifier' => '33333333-3333-4333-8333-333333333333', 'parent_routine_identifier' => $parent, 'account_identifier' => $owner, 'routine_name' => 'Available child', 'available' => true],
            ['routine_identifier' => '44444444-4444-4444-8444-444444444444', 'parent_routine_identifier' => $parent, 'account_identifier' => $owner, 'routine_name' => 'Unavailable child', 'available' => false],
        ]);

        foreach (["/api/accounts/{$owner}/posts", '/api/my/routines'] as $url) {
            $response = $this->withSession(['account_identifier' => $owner])->getJson($url)->assertOk()->assertJsonPath('total', 2);
            $items = collect($response->json('items'))->keyBy('routine_identifier');
            self::assertNull($items[$parent]['post_identifier']);
            self::assertSame(1, $items[$parent]['customization_count']);
        }
    }

    public function test_execution_count_counts_only_available_action_posts_even_without_routine_post(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        foreach ([['33333333-3333-4333-8333-333333333333', true], ['44444444-4444-4444-8444-444444444444', false]] as [$post, $available]) {
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'action', 'available' => $available]);
        }
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines')->assertOk()
            ->assertJsonPath('items.0.post_identifier', null)->assertJsonPath('items.0.execution_count', 1);
    }

    public function test_posted_routine_matches_account_posts_response(): void
    {
        $account = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $post = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'private', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $account, 'routine_name' => 'Routine', 'routine_execution_minutes' => 15, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'routine', 'post_like_count' => 3, 'post_support_count' => 4, 'available' => true, 'created_at' => '2026-09-09 10:00:00', 'updated_at' => now()]);
        DB::table('likes')->insert(['account_identifier' => $account, 'post_identifier' => $post, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tags')->insert(['tag_identifier' => '44444444-4444-4444-8444-444444444444', 'tag_name' => 'Tag', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => '44444444-4444-4444-8444-444444444444', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => '55555555-5555-4555-8555-555555555555', 'routine_identifier' => $routine, 'action_name' => 'Action', 'action_minutes' => 5, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $routine, 'post_category' => 'action', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);

        $posts = $this->withSession(['account_identifier' => $account])->getJson("/api/accounts/{$account}/posts")->assertOk()->json('items.0');
        $routines = $this->withSession(['account_identifier' => $account])->getJson('/api/my/routines')->assertOk()->json('items.0');
        self::assertSame($posts, $routines);
        self::assertSame(1, $routines['execution_count']);
        self::assertSame('Tag', $routines['tags'][0]['tag_name']);
        self::assertSame('Action', $routines['routine_actions'][0]['action_name']);
    }

    public function test_paginates_own_available_routines_and_ignores_disabled_posts_and_other_owners(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['account_identifier' => $other, 'account_name' => 'Other', 'email_address' => 'other@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $ids = ['33333333-3333-4333-8333-333333333333', '44444444-4444-4444-8444-444444444444', '55555555-5555-4555-8555-555555555555', '66666666-6666-4666-8666-666666666666'];
        foreach ($ids as $index => $id) {
            DB::table('routines')->insert(['routine_identifier' => $id, 'account_identifier' => $index === 2 ? $other : $owner, 'routine_name' => 'Routine', 'available' => $index !== 3, 'created_at' => '2026-09-0'.($index + 1).' 10:00:00', 'updated_at' => now()]);
        }
        DB::table('posts')->insert(['post_identifier' => '77777777-7777-4777-8777-777777777777', 'routine_identifier' => $ids[1], 'post_category' => 'routine', 'post_like_count' => 42, 'available' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?page=1&number_of_items_per_page=1')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('items.0.routine_identifier', $ids[1])
            ->assertJsonPath('items.0.post_identifier', null)->assertJsonPath('items.0.post_like_count', null)->assertJsonPath('items.0.liked', null);
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?page=2&number_of_items_per_page=1')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('items.0.routine_identifier', $ids[0]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/my/routines')->assertUnauthorized();
    }

    public function test_posted_routines_follow_existing_post_pagination_order(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([1, 2] as $n) {
            $routine = "00000000-0000-4000-8000-00000000000{$n}";
            $post = "10000000-0000-4000-8000-00000000000{$n}";
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true, 'created_at' => "2026-09-0{$n} 10:00:00", 'updated_at' => now()]);
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => true, 'created_at' => '2026-09-0'.(3 - $n).' 10:00:00', 'updated_at' => now()]);
        }
        $posts = $this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts?number_of_items_per_page=1")->assertOk()->json();
        $routines = $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?number_of_items_per_page=1')->assertOk()->json();
        self::assertSame($posts, $routines);
        self::assertSame(2, $routines['total']);
        self::assertSame('10000000-0000-4000-8000-000000000001', $routines['items'][0]['post_identifier']);
    }

    public function test_unposted_child_keeps_identity_and_hides_parent_origin_by_visibility_and_bilateral_blocks(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $author = '22222222-2222-4222-8222-222222222222';
        $parent = '33333333-3333-4333-8333-333333333333';
        $child = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com', 'visibility' => 'private', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('routines')->insert(['routine_identifier' => $parent, 'account_identifier' => $author, 'routine_name' => 'Private', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => $child, 'parent_routine_identifier' => $parent, 'account_identifier' => $owner, 'routine_name' => 'Child', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        $origin = ['routine_identifier' => $parent, 'routine_name' => 'Private', 'account_name' => 'Author', 'icon_image_url' => null];
        $assertChild = function ($response, ?array $expectedOrigin) use ($child): void {
            $response->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.routine_identifier', $child);
            $item = $response->json('items.0');
            self::assertArrayHasKey('customization_origin', $item);
            self::assertSame($expectedOrigin, $item['customization_origin']);
        };
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), null);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), null);
        DB::table('follows')->insert(['following_account_identifier' => $owner, 'followed_account_identifier' => $author]);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), $origin);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), $origin);
        DB::table('routines')->where('routine_identifier', $parent)->update(['available' => false]);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), null);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), null);
        DB::table('routines')->where('routine_identifier', $parent)->update(['available' => true]);
        DB::table('accounts')->where('account_identifier', $author)->update(['available' => false]);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), null);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), null);
        DB::table('accounts')->where('account_identifier', $author)->update(['available' => true, 'status' => 'suspended']);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), null);
        $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), null);
        DB::table('accounts')->where('account_identifier', $author)->update(['status' => 'active']);
        foreach ([[$author, $owner], [$owner, $author]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked, 'created_at' => now(), 'updated_at' => now()]);
            $assertChild($this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/posts"), null);
            $assertChild($this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines'), null);
            DB::table('blocks')->delete();
        }
    }

    public function test_query_count_does_not_grow_with_routine_count(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 10) as $number) {
            $routine = sprintf('00000000-0000-4000-8000-%012d', $number);
            $post = sprintf('10000000-0000-4000-8000-%012d', $number);
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?number_of_items_per_page=1')->assertOk();
        $one = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?number_of_items_per_page=10')->assertOk();
        self::assertSame($one, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }

    public function test_selects_first_available_post_and_stably_paginates_null_timestamps(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 4) as $number) {
            DB::table('routines')->insert(['routine_identifier' => sprintf('00000000-0000-4000-8000-%012d', $number), 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true, 'created_at' => $number === 4 ? null : '2026-09-05 10:00:00', 'updated_at' => now()]);
        }
        $first = '10000000-0000-4000-8000-000000000001';
        foreach ([
            [$first, 1, '2026-09-01 10:00:00', true, 7],
            ['10000000-0000-4000-8000-000000000002', 1, '2026-09-01 10:00:00', true, 8],
            ['10000000-0000-4000-8000-000000000003', 1, '2026-09-02 10:00:00', true, 9],
            ['10000000-0000-4000-8000-000000000004', 1, null, true, 10],
            ['10000000-0000-4000-8000-000000000005', 1, '2026-08-01 10:00:00', false, 11],
            ['10000000-0000-4000-8000-000000000006', 2, null, true, 12],
        ] as [$post, $number, $createdAt, $available, $likes]) {
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => sprintf('00000000-0000-4000-8000-%012d', $number), 'post_category' => 'routine', 'post_like_count' => $likes, 'available' => $available, 'created_at' => $createdAt, 'updated_at' => now()]);
        }
        $items = [];
        DB::enableQueryLog();
        DB::flushQueryLog();
        foreach (range(1, 4) as $page) {
            $response = $this->withSession(['account_identifier' => $owner])->getJson("/api/my/routines?page={$page}&number_of_items_per_page=1")->assertOk()->assertJsonPath('total', 4);
            $items[] = $response->json('items.0');
        }
        self::assertSame(['00000000-0000-4000-8000-000000000002', '00000000-0000-4000-8000-000000000003', '00000000-0000-4000-8000-000000000001', '00000000-0000-4000-8000-000000000004'], array_column($items, 'routine_identifier'));
        self::assertSame($first, $items[2]['post_identifier']);
        self::assertSame(7, $items[2]['post_like_count']);
        self::assertNull($items[0]['posted_at']);
        self::assertNull($items[1]['post_identifier']);
        self::assertNull($items[3]['post_identifier']);
        DB::disableQueryLog();
    }

    public function test_resolves_each_visible_parent_author_image_only_once_per_request(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $author = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['account_identifier' => $author, 'account_name' => 'Author', 'email_address' => 'author@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach (range(1, 3) as $number) {
            $parent = sprintf('30000000-0000-4000-8000-%012d', $number);
            DB::table('routines')->insert(['routine_identifier' => $parent, 'account_identifier' => $author, 'routine_name' => 'Parent', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('routines')->insert(['routine_identifier' => sprintf('40000000-0000-4000-8000-%012d', $number), 'parent_routine_identifier' => $parent, 'account_identifier' => $owner, 'routine_name' => 'Child', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $images = new class implements AccountImageUrlServiceInterface
        {
            public array $calls = [];

            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                $this->calls[] = $accountIdentifier->value();

                return null;
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return null;
            }
        };
        $this->app->instance(AccountImageUrlServiceInterface::class, $images);
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines')->assertOk()->assertJsonPath('total', 3);
        self::assertCount(2, $images->calls);
        self::assertEqualsCanonicalizing([$owner, $author], $images->calls);
    }
}
