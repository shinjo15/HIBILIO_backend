<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetMyPostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_my_routine_post_and_my_execution_of_another_accounts_routine(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $myRoutine = '33333333-3333-4333-8333-333333333333';
        $otherRoutine = '44444444-4444-4444-8444-444444444444';
        $execution = '55555555-5555-4555-8555-555555555555';
        foreach ([$me, $other] as $account) {
            DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => $account, 'email_address' => $account.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        foreach ([[$myRoutine, $me], [$otherRoutine, $other]] as [$routine, $owner]) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        }
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $me, 'routine_identifier' => $otherRoutine, 'executed_at' => '2026-01-01 00:00:00', 'routine_execution_memo' => 'memo']);
        DB::table('posts')->insert([
            ['post_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $myRoutine, 'routine_execution_identifier' => null, 'post_category' => 'routine', 'available' => true, 'created_at' => '2026-01-01 00:00:00'],
            ['post_identifier' => '77777777-7777-4777-8777-777777777777', 'routine_identifier' => $otherRoutine, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true, 'created_at' => '2026-01-02 00:00:00'],
        ]);
        DB::table('posts')->where('post_identifier', '66666666-6666-4666-8666-666666666666')->update(['post_like_count' => 3]);
        DB::table('posts')->where('post_identifier', '77777777-7777-4777-8777-777777777777')->update(['post_support_count' => 4]);
        DB::table('likes')->insert(['account_identifier' => $me, 'post_identifier' => '66666666-6666-4666-8666-666666666666']);
        DB::table('supports')->insert(['account_identifier' => $me, 'post_identifier' => '77777777-7777-4777-8777-777777777777']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.post_category', 'action')
            ->assertJsonPath('items.0.account_identifier', $me)
            ->assertJsonPath('items.0.routine_execution_identifier', $execution)
            ->assertJsonPath('items.0.routine_execution_memo', 'memo')
            ->assertJsonPath('items.0.supported', true)
            ->assertJsonPath('items.0.support_count', 4)
            ->assertJsonPath('items.1.post_category', 'routine')
            ->assertJsonPath('items.1.post_like_count', 3)
            ->assertJsonPath('items.1.liked', true);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/my/posts')->assertUnauthorized();
    }

    public function test_repeated_posts_batch_database_queries_and_icon_lookup(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        DB::table('accounts')->insert(['account_identifier' => $me, 'account_name' => 'Me', 'email_address' => 'me@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $me, 'routine_name' => 'Routine', 'available' => true]);
        for ($i = 1; $i <= 10; $i++) {
            DB::table('posts')->insert(['post_identifier' => sprintf('33333333-3333-4333-8333-%012d', $i), 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => true]);
        }
        $images = new class implements AccountImageUrlServiceInterface
        {
            public int $calls = 0;

            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                $this->calls++;

                return null;
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return null;
            }
        };
        $this->app->instance(AccountImageUrlServiceInterface::class, $images);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 10);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        self::assertLessThanOrEqual(5, $count);
        self::assertSame(1, $images->calls);
    }

    public function test_private_blocked_and_inactive_routine_authors_are_not_exposed(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $owner = '22222222-2222-4222-8222-222222222222';
        $routine = '33333333-3333-4333-8333-333333333333';
        $execution = '44444444-4444-4444-8444-444444444444';
        foreach ([$me, $owner] as $account) {
            DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => $account, 'email_address' => $account.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Secret', 'available' => true]);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $me, 'routine_identifier' => $routine, 'executed_at' => '2026-01-01 00:00:00']);
        DB::table('posts')->insert(['post_identifier' => '55555555-5555-4555-8555-555555555555', 'routine_identifier' => $routine, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true]);

        DB::table('accounts')->where('account_identifier', $owner)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $owner]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.posted_at', null);
        DB::table('blocks')->insert(['blocking_account_identifier' => $owner, 'blocked_account_identifier' => $me]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('blocks')->delete();
        DB::table('blocks')->insert(['blocking_account_identifier' => $me, 'blocked_account_identifier' => $owner]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('blocks')->delete();
        DB::table('accounts')->where('account_identifier', $owner)->update(['status' => 'inactive']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('accounts')->where('account_identifier', $owner)->update(['status' => 'active', 'available' => false]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('accounts')->where('account_identifier', $owner)->update(['available' => true]);
        DB::table('posts')->where('routine_identifier', $routine)->update(['available' => false]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
        DB::table('posts')->where('routine_identifier', $routine)->update(['available' => true]);
        DB::table('routines')->where('routine_identifier', $routine)->update(['available' => false]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_origin_is_hidden_when_private_blocked_or_unavailable_but_unposted_visible_origin_is_returned(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $owner = '22222222-2222-4222-8222-222222222222';
        $parent = '33333333-3333-4333-8333-333333333333';
        $child = '44444444-4444-4444-8444-444444444444';
        foreach ([$me, $owner] as $account) {
            DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => $account, 'email_address' => $account.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        DB::table('routines')->insert(['routine_identifier' => $parent, 'account_identifier' => $owner, 'routine_name' => 'Parent', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $child, 'parent_routine_identifier' => $parent, 'account_identifier' => $me, 'routine_name' => 'Child', 'available' => true]);
        DB::table('posts')->insert(['post_identifier' => '55555555-5555-4555-8555-555555555555', 'routine_identifier' => $child, 'post_category' => 'routine', 'available' => true]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('items.0.customization_origin.routine_identifier', $parent);
        DB::table('accounts')->where('account_identifier', $owner)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('items.0.customization_origin', null);
        DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $owner]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('items.0.customization_origin.routine_identifier', $parent);
        DB::table('blocks')->insert(['blocking_account_identifier' => $owner, 'blocked_account_identifier' => $me]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('items.0.customization_origin', null);
        DB::table('blocks')->delete();
        DB::table('routines')->where('routine_identifier', $parent)->update(['available' => false]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts')->assertOk()->assertJsonPath('items.0.customization_origin', null);
    }

    public function test_mismatched_and_other_accounts_executions_are_excluded_and_pagination_is_stable(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $myRoutine = '33333333-3333-4333-8333-333333333333';
        $otherRoutine = '44444444-4444-4444-8444-444444444444';
        foreach ([$me, $other] as $account) {
            DB::table('accounts')->insert(['account_identifier' => $account, 'account_name' => $account, 'email_address' => $account.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        foreach ([[$myRoutine, $me], [$otherRoutine, $other]] as [$routine, $owner]) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        }
        $first = '55555555-5555-4555-8555-555555555555';
        $second = '66666666-6666-4666-8666-666666666666';
        $theirs = '77777777-7777-4777-8777-777777777777';
        foreach ([[$first, $me], [$second, $me], [$theirs, $other]] as [$execution, $executor]) {
            DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $executor, 'routine_identifier' => $myRoutine, 'executed_at' => '2026-01-01 00:00:00']);
        }
        foreach ([['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $myRoutine, $first], ['bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $myRoutine, $second], ['cccccccc-cccc-4ccc-8ccc-cccccccccccc', $myRoutine, $theirs], ['dddddddd-dddd-4ddd-8ddd-dddddddddddd', $otherRoutine, $first], ['eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $myRoutine, null]] as [$post, $routine, $execution]) {
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true, 'created_at' => '2026-01-01 00:00:00']);
        }
        foreach ([1 => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 2 => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'] as $page => $expected) {
            $this->withSession(['account_identifier' => $me])->getJson('/api/my/posts?page='.$page.'&number_of_items_per_page=1')
                ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'items')->assertJsonPath('items.0.post_identifier', $expected);
        }
    }
}
