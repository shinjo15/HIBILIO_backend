<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetAccountPostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_include_execution_by_target_of_another_accounts_routine_but_not_unposted_routines(): void
    {
        $target = '11111111-1111-4111-8111-111111111111';
        $author = '22222222-2222-4222-8222-222222222222';
        $own = '33333333-3333-4333-8333-333333333333';
        $other = '44444444-4444-4444-8444-444444444444';
        $unposted = '55555555-5555-4555-8555-555555555555';
        $execution = '66666666-6666-4666-8666-666666666666';
        foreach ([$target, $author] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => $id, 'email_address' => $id.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        foreach ([[$own, $target], [$other, $author], [$unposted, $target]] as [$routine, $owner]) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        }
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $target, 'routine_identifier' => $other, 'executed_at' => '2026-01-01 00:00:00', 'routine_execution_memo' => 'memo']);
        DB::table('posts')->insert([
            ['post_identifier' => '77777777-7777-4777-8777-777777777777', 'routine_identifier' => $own, 'routine_execution_identifier' => null, 'post_category' => 'routine', 'available' => true, 'created_at' => '2026-01-01 00:00:00'],
            ['post_identifier' => '88888888-8888-4888-8888-888888888888', 'routine_identifier' => $other, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true, 'created_at' => '2026-01-02 00:00:00'],
        ]);
        DB::table('posts')->where('post_identifier', '77777777-7777-4777-8777-777777777777')->update(['post_like_count' => 3, 'post_support_count' => 7]);
        DB::table('posts')->where('post_identifier', '88888888-8888-4888-8888-888888888888')->update(['post_support_count' => 4]);
        DB::table('likes')->insert(['account_identifier' => $target, 'post_identifier' => '77777777-7777-4777-8777-777777777777']);
        DB::table('supports')->insert(['account_identifier' => $target, 'post_identifier' => '88888888-8888-4888-8888-888888888888']);
        $this->getJson("/api/accounts/{$target}/posts")->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('items.0.post_category', 'action')->assertJsonPath('items.0.account_identifier', $target)
            ->assertJsonPath('items.0.routine_execution_memo', 'memo')->assertJsonPath('items.1.post_category', 'routine');
        $this->getJson("/api/accounts/{$author}/posts")->assertOk()->assertJsonPath('total', 0);
        // /posts intentionally excludes unposted routines; /routines still includes them.
        $this->getJson("/api/accounts/{$target}/routines")->assertOk()->assertJsonPath('total', 2);

        $mine = $this->withSession(['account_identifier' => $target])->getJson('/api/my/posts')->assertOk()->assertJsonPath('total', 2)->json('items');
        $public = $this->withSession(['account_identifier' => $target])->getJson("/api/accounts/{$target}/posts")->assertOk()->assertJsonPath('total', 2)->json('items');
        foreach ($mine as $index => $item) {
            foreach ($item as $field => $value) {
                self::assertArrayHasKey($field, $public[$index]);
                self::assertSame($value, $public[$index][$field], "Mismatch for {$item['post_category']}.{$field}");
            }
        }
        self::assertSame(4, $public[0]['support_count']);
        self::assertSame(4, $public[0]['post_support_count']);

        $oldRoutine = $this->withSession(['account_identifier' => $target])->getJson("/api/accounts/{$target}/routines")
            ->assertOk()->assertJsonPath('total', 2)->json('items');
        $oldPosted = collect($oldRoutine)->firstWhere('routine_identifier', $own);
        self::assertNotNull($oldPosted);
        self::assertSame(7, $oldPosted['post_support_count']);
        foreach ($oldPosted as $field => $value) {
            self::assertArrayHasKey($field, $public[1]);
            self::assertSame($value, $public[1][$field], "Mismatch for old routine field {$field}");
        }
    }

    public function test_viewer_visibility_interactions_and_bilateral_blocks(): void
    {
        $target = '11111111-1111-4111-8111-111111111111';
        $viewer = '22222222-2222-4222-8222-222222222222';
        $author = '33333333-3333-4333-8333-333333333333';
        $routine = '44444444-4444-4444-8444-444444444444';
        $execution = '55555555-5555-4555-8555-555555555555';
        $post = '66666666-6666-4666-8666-666666666666';
        foreach ([$target, $viewer, $author] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => $id, 'email_address' => $id.'@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        }
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $author, 'routine_name' => 'Routine', 'available' => true]);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $target, 'routine_identifier' => $routine, 'executed_at' => '2026-01-01 00:00:00']);
        DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true, 'created_at' => '2026-01-01 00:00:00', 'post_support_count' => 4]);
        DB::table('likes')->insert(['account_identifier' => $viewer, 'post_identifier' => $post]);
        DB::table('supports')->insert(['account_identifier' => $viewer, 'post_identifier' => $post]);
        $url = "/api/accounts/{$target}/posts";
        $this->getJson($url)->assertOk()->assertJsonPath('items.0.liked', false)->assertJsonPath('items.0.supported', false)->assertJsonPath('items.0.post_support_count', 4);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('items.0.liked', true)->assertJsonPath('items.0.supported', true);
        DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
        $this->flushSession()->getJson($url)->assertOk()->assertJsonPath('total', 0);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $author]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        foreach ([[$viewer, $author], [$author, $viewer]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
            DB::table('blocks')->delete();
        }
        foreach ([[$viewer, $target], [$target, $viewer]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
            DB::table('blocks')->delete();
        }
        DB::table('accounts')->where('account_identifier', $target)->update(['visibility' => 'private']);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $target]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 1);
        DB::table('accounts')->where('account_identifier', $target)->update(['status' => 'inactive']);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('total', 0);
    }

    public function test_customization_origin_is_filtered_using_viewer_not_target(): void
    {
        $target = '11111111-1111-4111-8111-111111111111';
        $viewer = '22222222-2222-4222-8222-222222222222';
        $originAuthor = '33333333-3333-4333-8333-333333333333';
        $parent = '44444444-4444-4444-8444-444444444444';
        $child = '55555555-5555-4555-8555-555555555555';
        foreach ([$target, $viewer, $originAuthor] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => $id, 'email_address' => $id.'@example.com', 'visibility' => $id === $originAuthor ? 'private' : 'public', 'status' => 'active', 'available' => true]);
        }
        DB::table('routines')->insert(['routine_identifier' => $parent, 'account_identifier' => $originAuthor, 'routine_name' => 'Parent', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $child, 'parent_routine_identifier' => $parent, 'account_identifier' => $target, 'routine_name' => 'Child', 'available' => true]);
        DB::table('posts')->insert(['post_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $child, 'post_category' => 'routine', 'available' => true]);
        DB::table('follows')->insert(['following_account_identifier' => $target, 'followed_account_identifier' => $originAuthor]);
        $url = "/api/accounts/{$target}/posts";
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('items.0.customization_origin', null);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $originAuthor]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('items.0.customization_origin.routine_identifier', $parent);
        DB::table('blocks')->insert(['blocking_account_identifier' => $originAuthor, 'blocked_account_identifier' => $viewer]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertJsonPath('items.0.customization_origin', null);
    }

    public function test_mixed_posts_are_ordered_and_paged_by_post_not_routine(): void
    {
        $target = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $execution = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $target, 'account_name' => 'Target', 'email_address' => 'target@example.com', 'visibility' => 'public', 'status' => 'active', 'available' => true]);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $target, 'routine_name' => 'Routine', 'available' => true]);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => $target, 'routine_identifier' => $routine, 'executed_at' => '2026-01-01 00:00:00']);
        foreach ([['44444444-4444-4444-8444-444444444444', 'routine', null], ['55555555-5555-4555-8555-555555555555', 'action', $execution]] as [$post, $category, $executionId]) {
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'routine_execution_identifier' => $executionId, 'post_category' => $category, 'available' => true, 'created_at' => '2026-01-01 00:00:00']);
        }
        DB::table('posts')->insert(['post_identifier' => '66666666-6666-4666-8666-666666666666', 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => false, 'created_at' => '2026-01-02 00:00:00']);
        foreach ([1 => '44444444-4444-4444-8444-444444444444', 2 => '55555555-5555-4555-8555-555555555555'] as $page => $post) {
            $this->getJson("/api/accounts/{$target}/posts?page={$page}&number_of_items_per_page=1")
                ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'items')->assertJsonPath('items.0.post_identifier', $post);
        }
        $this->getJson("/api/accounts/{$target}/posts?page=3&number_of_items_per_page=1")
            ->assertOk()->assertExactJson(['items' => [], 'total' => 2]);
    }

    public function test_pagination_http_validation_boundaries(): void
    {
        $url = '/api/accounts/11111111-1111-4111-8111-111111111111/posts';
        foreach (['page=1&number_of_items_per_page=1', 'page=1&number_of_items_per_page=120'] as $query) {
            $this->getJson("{$url}?{$query}")->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        }
        foreach (['page=0', 'page=-1', 'page=abc', 'page=', 'number_of_items_per_page=0', 'number_of_items_per_page=121', 'number_of_items_per_page=abc', 'number_of_items_per_page='] as $query) {
            $this->getJson("{$url}?{$query}")->assertUnprocessable();
        }
    }
}
