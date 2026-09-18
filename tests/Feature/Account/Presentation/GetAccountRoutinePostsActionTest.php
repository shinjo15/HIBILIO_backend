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

        $anonymousResponse = $this->getJson("/api/accounts/{$accountIdentifier}/posts")
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333')->assertJsonPath('items.0.icon_image_url', null)->assertJsonPath('items.0.liked', false)->assertJsonPath('total', 1);
        self::assertArrayNotHasKey('supported', $anonymousResponse->json('items.0'));

        $myPostsResponse = $this->withSession(['account_identifier' => $accountIdentifier])->getJson('/api/my/posts')
            ->assertOk()->assertJsonPath('items.0.post_identifier', '33333333-3333-4333-8333-333333333333')->assertJsonPath('items.0.icon_image_url', null)->assertJsonPath('items.0.liked', true);
        self::assertArrayNotHasKey('supported', $myPostsResponse->json('items.0'));
    }

    public function test_returns_unauthorized_when_not_logged_in(): void
    {
        $this->getJson('/api/my/posts')->assertUnauthorized();
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
}
