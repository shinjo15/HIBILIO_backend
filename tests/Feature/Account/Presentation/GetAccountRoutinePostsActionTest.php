<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInput;
use Src\Account\Infrastructure\Query\GetAccountRoutinePosts\GetAccountRoutinePosts;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class GetAccountRoutinePostsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_account_lists_one_available_post_per_routine_and_visible_unposted_routines(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $posted = '22222222-2222-4222-8222-222222222222';
        $unposted = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([$posted, $unposted] as $routine) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([['44444444-4444-4444-8444-444444444444', '2026-09-01 10:00:00', true, 'routine'], ['55555555-5555-4555-8555-555555555555', '2026-09-02 10:00:00', true, 'routine'], ['66666666-6666-4666-8666-666666666666', '2026-09-03 10:00:00', false, 'routine'], ['77777777-7777-4777-8777-777777777777', '2026-09-04 10:00:00', true, 'action']] as [$post, $created, $available, $category]) {
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $posted, 'post_category' => $category, 'available' => $available, 'created_at' => $created, 'updated_at' => now()]);
        }
        DB::table('posts')->where('post_identifier', '44444444-4444-4444-8444-444444444444')->update(['post_like_count' => 7, 'post_support_count' => 11]);
        DB::table('likes')->insert(['account_identifier' => $owner, 'post_identifier' => '44444444-4444-4444-8444-444444444444', 'created_at' => now(), 'updated_at' => now()]);
        $publicItems = $this->getJson("/api/accounts/{$owner}/routines?page=1&number_of_items_per_page=10")
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, 'items')->json('items');
        $publicByRoutine = collect($publicItems)->keyBy('routine_identifier');
        self::assertSame('44444444-4444-4444-8444-444444444444', $publicByRoutine[$posted]['post_identifier']);
        self::assertSame('2026-09-01T10:00:00+00:00', $publicByRoutine[$posted]['posted_at']);
        self::assertSame(7, $publicByRoutine[$posted]['post_like_count']);
        self::assertSame(11, $publicByRoutine[$posted]['post_support_count']);
        self::assertFalse($publicByRoutine[$posted]['liked']);
        self::assertNull($publicByRoutine[$unposted]['post_identifier']);
        self::assertNull($publicByRoutine[$unposted]['posted_at']);
        self::assertNull($publicByRoutine[$unposted]['post_like_count']);
        self::assertNull($publicByRoutine[$unposted]['post_support_count']);
        self::assertNull($publicByRoutine[$unposted]['liked']);

        $own = $this->withSession(['account_identifier' => $owner])->getJson("/api/accounts/{$owner}/routines?number_of_items_per_page=10")
            ->assertOk()->assertJsonPath('total', 2)->json('items');
        $ownByRoutine = collect($own)->keyBy('routine_identifier');
        self::assertNull($ownByRoutine[$unposted]['post_identifier']);
        self::assertNull($ownByRoutine[$unposted]['posted_at']);
        self::assertNull($ownByRoutine[$unposted]['post_like_count']);
        self::assertNull($ownByRoutine[$unposted]['post_support_count']);
        self::assertNull($ownByRoutine[$unposted]['liked']);
        self::assertSame('44444444-4444-4444-8444-444444444444', $ownByRoutine[$posted]['post_identifier']);
        self::assertSame('2026-09-01T10:00:00+00:00', $ownByRoutine[$posted]['posted_at']);
        self::assertSame(7, $ownByRoutine[$posted]['post_like_count']);
        self::assertSame(11, $ownByRoutine[$posted]['post_support_count']);
        self::assertTrue($ownByRoutine[$posted]['liked']);

        $myRoutines = $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines?number_of_items_per_page=10')
            ->assertOk()->assertJsonPath('total', 2)->json('items');
        self::assertSame($own, $myRoutines);

        foreach (["/api/accounts/{$owner}/routines", '/api/my/routines'] as $url) {
            $paged = [];
            foreach ([1, 2] as $page) {
                $paged[] = $this->withSession(['account_identifier' => $owner])->getJson("{$url}?page={$page}&number_of_items_per_page=1")
                    ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'items')->json('items.0');
            }
            self::assertSame($myRoutines, $paged);
            $this->withSession(['account_identifier' => $owner])->getJson("{$url}?page=3&number_of_items_per_page=1")
                ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(0, 'items');
        }
    }

    public function test_post_with_missing_timestamp_returns_null_for_both_routes(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $routine = '22222222-2222-4222-8222-222222222222';
        $post = '33333333-3333-4333-8333-333333333333';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert(['routine_identifier' => $routine, 'account_identifier' => $owner, 'routine_name' => 'Routine', 'available' => true]);
        DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => true, 'created_at' => null]);
        $response = $this->getJson("/api/accounts/{$owner}/routines")->assertOk()->assertJsonPath('items.0.post_identifier', $post);
        self::assertNull($response->json('items.0.posted_at'));
        $this->withSession(['account_identifier' => $owner])->getJson('/api/my/routines')->assertOk()->assertJsonPath('items.0.posted_at', null);
    }

    public function test_returns_requested_accounts_routine_posts_and_logged_in_accounts_posts(): void
    {
        $accountIdentifier = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $accountIdentifier, 'account_name' => '作成者', 'email_address' => 'author@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => '22222222-2222-4222-8222-222222222222', 'account_identifier' => $accountIdentifier, 'routine_name' => '朝活', 'routine_execution_minutes' => 10, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => '33333333-3333-4333-8333-333333333333', 'routine_identifier' => '22222222-2222-4222-8222-222222222222', 'post_category' => 'routine', 'post_like_count' => 1, 'post_support_count' => 2, 'available' => true, 'created_at' => '2026-09-09 10:00:00', 'updated_at' => now()]);
        DB::table('likes')->insert(['account_identifier' => $accountIdentifier, 'post_identifier' => '33333333-3333-4333-8333-333333333333', 'created_at' => now(), 'updated_at' => now()]);

        $result = (new GetAccountRoutinePosts(new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return "https://images.example/accounts/{$accountIdentifier->value()}/icon";
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return null;
            }
        }))->execute(new GetAccountRoutinePostsInput($accountIdentifier, 1, 20, $accountIdentifier));

        self::assertSame("https://images.example/accounts/{$accountIdentifier}/icon", $result->items()[0]['iconImageUrl']);
        self::assertTrue($result->items()[0]['liked']);
        self::assertArrayNotHasKey('supported', $result->items()[0]);

        $anonymousResponse = $this->getJson("/api/accounts/{$accountIdentifier}/routines")
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333')->assertJsonPath('items.0.icon_image_url', null)->assertJsonPath('items.0.liked', false)->assertJsonPath('total', 1);
        self::assertArrayNotHasKey('supported', $anonymousResponse->json('items.0'));

        $myPostsResponse = $this->withSession(['account_identifier' => $accountIdentifier])->getJson('/api/my/routines')
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333')->assertJsonPath('items.0.icon_image_url', null)->assertJsonPath('items.0.liked', true);
        self::assertArrayNotHasKey('supported', $myPostsResponse->json('items.0'));
    }

    public function test_returns_unauthorized_when_not_logged_in(): void
    {
        $this->getJson('/api/my/routines')->assertUnauthorized();
    }

    public function test_returns_private_accounts_posts_only_to_the_owner_and_approved_follower(): void
    {
        $ownerIdentifier = '11111111-1111-4111-8111-111111111111';
        $followerIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = '33333333-3333-4333-8333-333333333333';
        $postIdentifier = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert([
            ['account_identifier' => $ownerIdentifier, 'account_name' => '鍵Account', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'private', 'created_at' => now(), 'updated_at' => now()],
            ['account_identifier' => $followerIdentifier, 'account_name' => '承認済みFollower', 'email_address' => 'follower@example.com', 'available' => true, 'status' => 'active', 'visibility' => 'public', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('follows')->insert(['following_account_identifier' => $followerIdentifier, 'followed_account_identifier' => $ownerIdentifier, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => $routineIdentifier, 'account_identifier' => $ownerIdentifier, 'routine_name' => '鍵Routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => $postIdentifier, 'routine_identifier' => $routineIdentifier, 'post_category' => 'routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->getJson("/api/accounts/{$ownerIdentifier}/posts")
            ->assertOk()
            ->assertExactJson(['items' => [], 'total' => 0]);

        foreach ([$ownerIdentifier, $followerIdentifier] as $viewerIdentifier) {
            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson("/api/accounts/{$ownerIdentifier}/posts")
                ->assertOk()
                ->assertJsonPath('total', 1)
                ->assertJsonPath('items.0.post_identifier', $postIdentifier);
        }
    }

    public function test_private_routines_show_unposted_items_only_to_approved_followers_and_respect_bilateral_blocks(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        $viewer = '22222222-2222-4222-8222-222222222222';
        $postedRoutine = '33333333-3333-4333-8333-333333333333';
        $unpostedRoutine = '44444444-4444-4444-8444-444444444444';
        DB::table('accounts')->insert([
            ['account_identifier' => $owner, 'account_name' => 'Owner', 'email_address' => 'owner@example.com', 'visibility' => 'private', 'available' => true, 'status' => 'active'],
            ['account_identifier' => $viewer, 'account_name' => 'Viewer', 'email_address' => 'viewer@example.com', 'visibility' => 'public', 'available' => true, 'status' => 'active'],
        ]);
        DB::table('routines')->insert([
            ['routine_identifier' => $postedRoutine, 'account_identifier' => $owner, 'routine_name' => 'Posted', 'available' => true],
            ['routine_identifier' => $unpostedRoutine, 'account_identifier' => $owner, 'routine_name' => 'Unposted', 'available' => true],
        ]);
        DB::table('posts')->insert(['post_identifier' => '55555555-5555-4555-8555-555555555555', 'routine_identifier' => $postedRoutine, 'post_category' => 'routine', 'available' => true, 'created_at' => now()]);
        $url = "/api/accounts/{$owner}/routines";
        $this->getJson($url)->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => $owner]);
        $items = collect($this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk()
            ->assertJsonPath('total', 2)->assertJsonCount(2, 'items')->json('items'))->keyBy('routine_identifier');
        self::assertSame('55555555-5555-4555-8555-555555555555', $items[$postedRoutine]['post_identifier']);
        self::assertNull($items[$unpostedRoutine]['post_identifier']);
        foreach ([[$viewer, $owner], [$owner, $viewer]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
            DB::table('blocks')->delete();
        }
    }
}
