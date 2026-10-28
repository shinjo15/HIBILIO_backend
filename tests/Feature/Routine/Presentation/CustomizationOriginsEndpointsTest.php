<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CustomizationOriginsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private const AUTHOR = '11111111-1111-4111-8111-111111111111';

    private const VIEWER = '22222222-2222-4222-8222-222222222222';

    private const PARENT_AUTHOR = '33333333-3333-4333-8333-333333333333';

    private const PARENT = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private const CHILD = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    private const POST = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    private const TAG = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([self::AUTHOR, self::VIEWER, self::PARENT_AUTHOR] as $id) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => '作者', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([[self::PARENT, null, '親'], [self::CHILD, self::PARENT, '子']] as [$id, $parent, $name]) {
            DB::table('routines')->insert(['routine_identifier' => $id, 'parent_routine_identifier' => $parent, 'account_identifier' => $id === self::PARENT ? self::PARENT_AUTHOR : self::AUTHOR, 'routine_name' => $name, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('posts')->insert(['post_identifier' => self::POST, 'routine_identifier' => self::CHILD, 'post_category' => 'routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tags')->insert(['tag_identifier' => self::TAG, 'tag_name' => 'tag', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_tags')->insert(['routine_identifier' => self::CHILD, 'tag_identifier' => self::TAG, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('follows')->insert(['following_account_identifier' => self::VIEWER, 'followed_account_identifier' => self::AUTHOR, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([self::AUTHOR, self::VIEWER] as $id) {
            DB::table('likes')->insert(['account_identifier' => $id, 'post_identifier' => self::POST, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('favorite_tags')->insert(['account_identifier' => $id, 'tag_identifier' => self::TAG, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public static function endpoints(): array
    {
        return [
            'customized' => ['/api/routines/'.self::PARENT.'/customized', 'items', null],
            'following' => ['/api/following/posts', 'posts', self::VIEWER],
            'favorite' => ['/api/posts/favorite_tags', 'posts', self::VIEWER],
            'popular' => ['/api/posts/popular', 'posts', null],
            'my posts' => ['/api/my/posts', 'items', self::AUTHOR],
            'account posts' => ['/api/accounts/'.self::AUTHOR.'/posts', 'items', null],
            'my likes' => ['/api/my/likes', 'items', self::AUTHOR],
            'account likes' => ['/api/accounts/'.self::VIEWER.'/likes', 'items', null],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_endpoint_returns_direct_origin(string $url, string $collection, ?string $viewer): void
    {
        if ($viewer !== null) {
            $this->withSession(['account_identifier' => $viewer]);
        }
        $response = $this->getJson($url)->assertOk()->assertJsonPath($collection.'.0.routine_identifier', self::CHILD);
        $response->assertJsonPath($collection.'.0.customization_origin', [
            'routine_identifier' => self::PARENT,
            'routine_name' => '親',
            'account_name' => '作者',
            'icon_image_url' => null,
        ]);
    }

    public static function privacyEndpoints(): array
    {
        $endpoints = self::endpoints();
        unset($endpoints['customized']);
        $endpoints['search'] = ['/api/routines/search?title=子', 'items', self::VIEWER];

        return $endpoints;
    }

    public static function allActionEndpoints(): array
    {
        return [
            'detail' => ['/api/routines/'.self::CHILD, null, self::VIEWER],
            'search' => ['/api/routines/search?title=子', 'items', self::VIEWER],
            'customized' => ['/api/routines/'.self::PARENT.'/customized', 'items', self::VIEWER],
            'following' => ['/api/following/posts', 'posts', self::VIEWER],
            'favorite' => ['/api/posts/favorite_tags', 'posts', self::VIEWER],
            'popular' => ['/api/posts/popular', 'posts', self::VIEWER],
            'my posts' => ['/api/my/posts', 'items', self::AUTHOR],
            'account posts' => ['/api/accounts/'.self::AUTHOR.'/posts', 'items', self::VIEWER],
            'my likes' => ['/api/my/likes', 'items', self::VIEWER],
            'account likes' => ['/api/accounts/'.self::AUTHOR.'/likes', 'items', self::VIEWER],
        ];
    }

    #[DataProvider('allActionEndpoints')]
    public function test_each_action_returns_exact_origin_fields_with_icon(string $url, ?string $collection, string $viewer): void
    {
        $this->app->instance(AccountImageUrlServiceInterface::class, new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return 'https://images.example/'.$accountIdentifier->value();
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return null;
            }
        });
        $response = $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk();
        $item = $collection === null ? $response->json() : $response->json($collection.'.0');
        self::assertSame([
            'routine_identifier' => self::PARENT,
            'routine_name' => '親',
            'account_name' => '作者',
            'icon_image_url' => 'https://images.example/'.self::PARENT_AUTHOR,
        ], $item['customization_origin']);
    }

    #[DataProvider('allActionEndpoints')]
    public function test_each_action_returns_a_real_signed_parent_icon_url(string $url, ?string $collection, string $viewer): void
    {
        config(['account.images.storage' => 'local']);
        $disk = Storage::persistentFake('account_images');
        $disk->diskName('account_images')->shouldServeSignedUrls(true, fn () => app('url'));
        $disk->put('accounts/'.self::PARENT_AUTHOR.'/icon/icon.webp', 'image-data');

        $response = $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertOk();
        $item = $collection === null ? $response->json() : $response->json($collection.'.0');
        $origin = $item['customization_origin'];
        $iconUrl = $origin['icon_image_url'];
        self::assertIsString($iconUrl);
        self::assertSame([
            'routine_identifier' => self::PARENT,
            'routine_name' => '親',
            'account_name' => '作者',
            'icon_image_url' => $iconUrl,
        ], $origin);
        self::assertStringContainsString('accounts/'.self::PARENT_AUTHOR.'/icon/icon.webp', $iconUrl);
        self::assertTrue(URL::hasValidSignature(Request::create($iconUrl), false));
        parse_str((string) parse_url($iconUrl, PHP_URL_QUERY), $query);
        self::assertArrayHasKey('expires', $query);
        self::assertEqualsWithDelta(now()->addHour()->timestamp, (int) $query['expires'], 3);
        $this->get($iconUrl)->assertOk();
        $this->get($iconUrl.'&tampered=1')->assertForbidden();
        $this->travel(61)->minutes();
        $this->get($iconUrl)->assertForbidden();
    }

    #[DataProvider('allActionEndpoints')]
    public function test_each_action_uses_actual_viewer_for_parent_visibility(string $url, ?string $collection, string $viewer): void
    {
        $itemPath = $collection === null ? null : $collection.'.0';
        $isCustomized = str_ends_with($url, '/customized');
        $get = function (string $id) use ($url, $itemPath): array {
            $response = $this->withSession(['account_identifier' => $id])->getJson($url)->assertOk();

            return $itemPath === null ? $response->json() : $response->json($itemPath);
        };
        $base = $get($viewer);
        self::assertSame([
            'routine_identifier' => self::PARENT,
            'routine_name' => '親',
            'account_name' => '作者',
            'icon_image_url' => null,
        ], $base['customization_origin']);
        self::assertSame('子', $base['routine_name']);
        self::assertSame(self::AUTHOR, $base['account_identifier']);
        DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update(['visibility' => 'private']);
        if ($isCustomized) {
            $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
        } else {
            $hidden = $get($viewer);
            self::assertNull($hidden['customization_origin']);
            unset($hidden['customization_origin'], $base['customization_origin']);
            self::assertSame($base, $hidden);
        }
        DB::table('follows')->insert(['following_account_identifier' => $viewer, 'followed_account_identifier' => self::PARENT_AUTHOR, 'created_at' => now(), 'updated_at' => now()]);
        self::assertSame(self::PARENT, $get($viewer)['customization_origin']['routine_identifier']);
        foreach ([[$viewer, self::PARENT_AUTHOR], [self::PARENT_AUTHOR, $viewer]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked, 'created_at' => now(), 'updated_at' => now()]);
            if ($isCustomized) {
                $this->withSession(['account_identifier' => $viewer])->getJson($url)->assertNotFound();
            } else {
                self::assertNull($get($viewer)['customization_origin']);
            }
            DB::table('blocks')->delete();
        }

        DB::table('follows')->insert(['following_account_identifier' => self::PARENT_AUTHOR, 'followed_account_identifier' => self::AUTHOR, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('favorite_tags')->insert(['account_identifier' => self::PARENT_AUTHOR, 'tag_identifier' => self::TAG, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('likes')->insert(['account_identifier' => self::PARENT_AUTHOR, 'post_identifier' => self::POST, 'created_at' => now(), 'updated_at' => now()]);
        if ($url === '/api/my/posts') {
            DB::table('routines')->where('routine_identifier', self::CHILD)->update(['account_identifier' => self::PARENT_AUTHOR]);
        }
        self::assertSame(self::PARENT, $get(self::PARENT_AUTHOR)['customization_origin']['routine_identifier']);
    }

    #[DataProvider('allActionEndpoints')]
    public function test_each_action_treats_inactive_and_unavailable_session_as_anonymous(string $url, ?string $collection, string $viewer): void
    {
        $requiresAuth = in_array($url, ['/api/my/posts', '/api/my/likes', '/api/following/posts', '/api/posts/favorite_tags'], true);
        DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update(['visibility' => 'private']);
        foreach ([['available' => false], ['available' => true, 'status' => 'temporarily_banned']] as $state) {
            DB::table('accounts')->where('account_identifier', $viewer)->update($state);
            $response = $this->withSession(['account_identifier' => $viewer])->getJson($url);
            if ($requiresAuth) {
                $response->assertUnauthorized();
            } elseif (str_ends_with($url, '/customized')) {
                $response->assertNotFound();
            } else {
                $response->assertOk();
                if ($viewer !== self::AUTHOR) {
                    $response->assertJsonPath($collection === null ? 'customization_origin' : $collection.'.0.customization_origin', null);
                }
            }
        }
    }

    public function test_authentication_required_lists_reject_anonymous_viewers(): void
    {
        foreach (['/api/my/posts', '/api/my/likes', '/api/following/posts', '/api/posts/favorite_tags'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_customized_endpoint_anonymous_private_parent_and_approved_follower(): void
    {
        $url = '/api/routines/'.self::PARENT.'/customized';
        DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update(['visibility' => 'private']);
        $this->getJson($url)->assertNotFound();
        $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertNotFound();
        DB::table('follows')->insert(['following_account_identifier' => self::VIEWER, 'followed_account_identifier' => self::PARENT_AUTHOR]);
        $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertOk()->assertJsonPath('items.0.routine_identifier', self::CHILD)->assertJsonPath('items.0.customization_origin.routine_identifier', self::PARENT);
    }

    public static function listActionEndpoints(): array
    {
        $endpoints = self::allActionEndpoints();
        unset($endpoints['detail']);

        return $endpoints;
    }

    #[DataProvider('listActionEndpoints')]
    public function test_each_list_preserves_exact_order_fields_and_total_across_pages_when_parent_becomes_private(string $url, ?string $collection, string $viewer): void
    {
        $hiddenAuthor = '44444444-4444-4444-8444-444444444444';
        $hiddenParent = '55555555-5555-4555-8555-555555555555';
        $middle = '66666666-6666-4666-8666-666666666666';
        $newest = '77777777-7777-4777-8777-777777777777';
        DB::table('accounts')->insert(['account_identifier' => $hiddenAuthor, 'account_name' => '非公開親作者', 'email_address' => 'hidden@example.com', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert(['routine_identifier' => $hiddenParent, 'account_identifier' => $hiddenAuthor, 'routine_name' => '非公開親', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        $customized = str_ends_with($url, '/customized');
        foreach ([[$middle, $customized ? self::PARENT : $hiddenParent, '88888888-8888-4888-8888-888888888888', 1], [$newest, $customized ? self::PARENT : null, '99999999-9999-4999-8999-999999999999', 2]] as [$routine, $parent, $post, $offset]) {
            DB::table('routines')->insert(['routine_identifier' => $routine, 'parent_routine_identifier' => $parent, 'account_identifier' => self::AUTHOR, 'routine_name' => '子'.$offset, 'available' => true, 'created_at' => now()->addSeconds($offset), 'updated_at' => now()]);
            DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $routine, 'post_category' => 'routine', 'post_like_count' => $offset + 1, 'available' => true, 'created_at' => now()->addSeconds($offset), 'updated_at' => now()]);
            DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => self::TAG]);
            foreach ([self::AUTHOR, self::VIEWER] as $id) {
                DB::table('likes')->insert(['account_identifier' => $id, 'post_identifier' => $post, 'created_at' => now()->addSeconds($offset), 'updated_at' => now()]);
            }
        }
        $separator = str_contains($url, '?') ? '&' : '?';
        $fetch = fn (int $page) => $this->withSession(['account_identifier' => $viewer])->getJson($url.$separator.'page='.$page.'&number_of_items_per_page=2')->assertOk()->assertJsonPath('total', 3)->json($collection);
        $before = array_merge($fetch(1), $fetch(2));
        self::assertSame([$newest, $middle, self::CHILD], array_column($before, 'routine_identifier'));
        foreach ($before as $item) {
            self::assertArrayHasKey('routine_name', $item);
            self::assertArrayHasKey('account_identifier', $item);
            self::assertArrayHasKey('account_name', $item);
            self::assertArrayHasKey('customization_count', $item);
        }
        DB::table('accounts')->where('account_identifier', $hiddenAuthor)->update(['visibility' => 'private']);
        $after = array_merge($fetch(1), $fetch(2));
        self::assertSame([$newest, $middle, self::CHILD], array_column($after, 'routine_identifier'));
        if (! $customized) {
            self::assertNull($after[0]['customization_origin']);
            self::assertNull($after[1]['customization_origin']);
            self::assertSame(self::PARENT, $after[2]['customization_origin']['routine_identifier']);
        }
        foreach ($after as &$item) {
            unset($item['customization_origin']);
        }
        foreach ($before as &$item) {
            unset($item['customization_origin']);
        }
        self::assertSame($before, $after);
    }

    #[DataProvider('privacyEndpoints')]
    public function test_each_list_keeps_items_and_hides_unviewable_parent(string $url, string $collection, ?string $viewer): void
    {
        DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update(['visibility' => 'private']);
        if ($viewer !== null) {
            $this->withSession(['account_identifier' => $viewer]);
        }
        $item = $this->getJson($url)->assertOk()->assertJsonPath('total', 1)->json($collection.'.0');
        self::assertSame(self::CHILD, $item['routine_identifier']);
        self::assertArrayHasKey('customization_origin', $item);
        self::assertNull($item['customization_origin']);
        DB::table('routines')->where('routine_identifier', self::CHILD)->update(['parent_routine_identifier' => null]);
        $normal = $this->getJson($url)->assertOk()->json($collection.'.0');
        self::assertArrayHasKey('customization_origin', $normal);
        self::assertNull($normal['customization_origin']);
    }

    #[DataProvider('endpoints')]
    public function test_each_list_batches_origin_queries_and_preserves_paging(string $url, string $collection, ?string $viewer): void
    {
        if ($viewer !== null) {
            $this->withSession(['account_identifier' => $viewer]);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson($url)->assertOk()->assertJsonPath('total', 1);
        $single = count(DB::getQueryLog());
        $newParent = '44444444-4444-4444-8444-444444444444';
        $newChild = '55555555-5555-4555-8555-555555555555';
        $newPost = '66666666-6666-4666-8666-666666666666';
        DB::table('routines')->insert(['routine_identifier' => $newParent, 'account_identifier' => self::PARENT_AUTHOR, 'routine_name' => '別の親', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => $newChild, 'parent_routine_identifier' => str_ends_with($url, '/customized') ? self::PARENT : $newParent, 'account_identifier' => self::AUTHOR, 'routine_name' => '子2', 'available' => true, 'created_at' => now()->addSecond(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => $newPost, 'routine_identifier' => $newChild, 'post_category' => 'routine', 'available' => true, 'created_at' => now()->addSecond(), 'updated_at' => now()]);
        DB::table('routine_tags')->insert(['routine_identifier' => $newChild, 'tag_identifier' => self::TAG, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([self::AUTHOR, self::VIEWER] as $id) {
            DB::table('likes')->insert(['account_identifier' => $id, 'post_identifier' => $newPost, 'created_at' => now()->addSecond(), 'updated_at' => now()]);
        }
        DB::flushQueryLog();
        $this->getJson($url)->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, $collection);
        self::assertSame($single, count(DB::getQueryLog()));
        DB::disableQueryLog();
        $first = $this->getJson($url.'?page=1&number_of_items_per_page=1')->assertOk()->assertJsonPath('total', 2)->json($collection.'.0.routine_identifier');
        $second = $this->getJson($url.'?page=2&number_of_items_per_page=1')->assertOk()->assertJsonPath('total', 2)->json($collection.'.0.routine_identifier');
        self::assertNotSame($first, $second);
        self::assertEqualsCanonicalizing([self::CHILD, $newChild], [$first, $second]);
    }

    public function test_parent_privacy_follow_owner_and_bilateral_blocks(): void
    {
        DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update(['visibility' => 'private']);
        $url = '/api/routines/'.self::CHILD;
        $this->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
        $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
        DB::table('follows')->insert(['following_account_identifier' => self::VIEWER, 'followed_account_identifier' => self::PARENT_AUTHOR, 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertOk()->assertJsonPath('customization_origin.routine_identifier', self::PARENT);
        $this->withSession(['account_identifier' => self::PARENT_AUTHOR])->getJson($url)->assertOk()->assertJsonPath('customization_origin.routine_identifier', self::PARENT);
        foreach ([[self::VIEWER, self::PARENT_AUTHOR], [self::PARENT_AUTHOR, self::VIEWER]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked, 'created_at' => now(), 'updated_at' => now()]);
            $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
            DB::table('blocks')->delete();
        }
    }

    public function test_parent_unavailable_and_author_inactive_or_unavailable_do_not_hide_child(): void
    {
        $url = '/api/routines/'.self::CHILD;
        DB::table('routines')->where('routine_identifier', self::PARENT)->update(['available' => false]);
        $this->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
        DB::table('routines')->where('routine_identifier', self::PARENT)->update(['available' => true]);
        foreach ([['available' => false], ['available' => true, 'status' => 'temporarily_banned']] as $state) {
            DB::table('accounts')->where('account_identifier', self::PARENT_AUTHOR)->update($state);
            $this->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
        }
        DB::table('routines')->where('routine_identifier', self::PARENT)->delete();
        $this->getJson($url)->assertOk()->assertJsonPath('customization_origin', null);
    }

    public function test_direct_parent_only_and_normal_routine_null(): void
    {
        $grandchild = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
        DB::table('routines')->insert(['routine_identifier' => $grandchild, 'parent_routine_identifier' => self::CHILD, 'account_identifier' => self::AUTHOR, 'routine_name' => '孫', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/routines/'.$grandchild)->assertOk()->assertJsonPath('customization_origin.routine_identifier', self::CHILD)->assertJsonPath('customization_origin.routine_name', '子');
        $this->getJson('/api/routines/'.self::PARENT)->assertOk()->assertJsonPath('customization_origin', null);
    }

    public function test_real_icon_service_generates_a_valid_short_lived_signed_url(): void
    {
        config(['account.images.storage' => 'local']);
        $disk = Storage::persistentFake('account_images');
        $disk->diskName('account_images')->shouldServeSignedUrls(true, fn () => app('url'));
        $disk->put('accounts/'.self::PARENT_AUTHOR.'/icon/icon.webp', 'image-data');
        $url = $this->getJson('/api/routines/'.self::CHILD)->assertOk()->json('customization_origin.icon_image_url');
        self::assertIsString($url);
        self::assertTrue(URL::hasValidSignature(Request::create($url), false));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertEqualsWithDelta(now()->addHour()->timestamp, (int) $query['expires'], 3);
        $this->get($url)->assertOk();
        $this->get($url.'&tampered=1')->assertForbidden();
        $this->travel(61)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_real_image_service_checks_each_visible_unique_parent_author_once_at_disk_boundary(): void
    {
        $hiddenAuthor = '44444444-4444-4444-8444-444444444444';
        $missingParent = '55555555-5555-4555-8555-555555555555';
        $hiddenParent = '66666666-6666-4666-8666-666666666666';
        DB::table('accounts')->insert(['account_identifier' => $hiddenAuthor, 'account_name' => '非公開', 'email_address' => 'private@example.com', 'visibility' => 'private', 'available' => true, 'status' => 'active']);
        DB::table('routines')->insert([
            ['routine_identifier' => $missingParent, 'parent_routine_identifier' => null, 'account_identifier' => self::VIEWER, 'routine_name' => '別の親', 'available' => true],
            ['routine_identifier' => $hiddenParent, 'parent_routine_identifier' => null, 'account_identifier' => $hiddenAuthor, 'routine_name' => '鍵の親', 'available' => true],
            ['routine_identifier' => '77777777-7777-4777-8777-777777777777', 'parent_routine_identifier' => self::PARENT, 'account_identifier' => self::AUTHOR, 'routine_name' => '選択2', 'available' => true],
            ['routine_identifier' => '88888888-8888-4888-8888-888888888888', 'parent_routine_identifier' => $missingParent, 'account_identifier' => self::AUTHOR, 'routine_name' => '選択3', 'available' => true],
            ['routine_identifier' => '99999999-9999-4999-8999-999999999999', 'parent_routine_identifier' => $hiddenParent, 'account_identifier' => self::AUTHOR, 'routine_name' => '選択4', 'available' => true],
        ]);
        DB::table('routines')->where('routine_identifier', self::CHILD)->update(['routine_name' => '選択1']);
        config(['account.images.storage' => 'local']);
        $disk = Storage::persistentFake('account_images');
        $disk->diskName('account_images')->shouldServeSignedUrls(true, fn () => app('url'));
        $visiblePath = 'accounts/'.self::PARENT_AUTHOR.'/icon/icon.webp';
        $missingPath = 'accounts/'.self::VIEWER.'/icon/icon.webp';
        $hiddenPath = 'accounts/'.$hiddenAuthor.'/icon/icon.webp';
        $disk->put($visiblePath, 'image-data');
        $disk->delete($missingPath);
        $disk->delete('accounts/'.self::AUTHOR.'/icon/icon.webp');
        $disk->put($hiddenPath, 'private-image');
        $spy = \Mockery::spy($disk)->makePartial();
        Storage::set('account_images', $spy);
        $items = $this->getJson('/api/routines/search?title=選択')->assertOk()->assertJsonPath('total', 4)->json('items');
        $byId = collect($items)->keyBy('routine_identifier');
        $url = $byId[self::CHILD]['customization_origin']['icon_image_url'];
        self::assertIsString($url);
        self::assertTrue(URL::hasValidSignature(Request::create($url), false));
        self::assertSame($url, $byId['77777777-7777-4777-8777-777777777777']['customization_origin']['icon_image_url']);
        self::assertNull($byId['88888888-8888-4888-8888-888888888888']['customization_origin']['icon_image_url']);
        self::assertNull($byId['99999999-9999-4999-8999-999999999999']['customization_origin']);
        $spy->shouldHaveReceived('exists')->with($visiblePath)->once();
        $spy->shouldHaveReceived('exists')->with($missingPath)->once();
        $spy->shouldNotHaveReceived('exists', [$hiddenPath]);
        $spy->shouldHaveReceived('exists')->with('accounts/'.self::AUTHOR.'/icon/icon.webp')->times(4);
        $spy->shouldHaveReceived('temporaryUrl')->withArgs(fn ($path): bool => $path === $visiblePath)->once();
        $spy->shouldHaveReceived('temporaryUrl')->once();
    }

    public function test_origin_has_no_separate_query_or_application_port(): void
    {
        self::assertDirectoryDoesNotExist(base_path('src/Routine/Application/Usecase/Query/GetCustomizationOrigins'));
        self::assertDirectoryDoesNotExist(base_path('src/Routine/Infrastructure/Query/GetCustomizationOrigins'));
        self::assertFileDoesNotExist(app_path('Http/Actions/Routine/CustomizationOriginResponse.php'));
    }

    public function test_mixed_search_and_following_only_add_origin_to_routine_items(): void
    {
        $execution = '77777777-7777-4777-8777-777777777777';
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'routine_identifier' => self::CHILD, 'executor_account_identifier' => self::AUTHOR, 'executed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => '88888888-8888-4888-8888-888888888888', 'routine_identifier' => self::CHILD, 'routine_execution_identifier' => $execution, 'post_category' => 'action', 'available' => true, 'created_at' => now()->addSecond(), 'updated_at' => now()]);
        foreach ([['/api/routines/search?title=子', 'items', 'item_type', 'routine_execution'], ['/api/following/posts', 'posts', 'post_category', 'action']] as [$url, $key, $type, $executionType]) {
            $items = $this->withSession(['account_identifier' => self::VIEWER])->getJson($url)->assertOk()->json($key);
            $executionItem = collect($items)->firstWhere($type, $executionType);
            $routineItem = collect($items)->firstWhere($type, 'routine');
            self::assertNotNull($executionItem);
            self::assertArrayNotHasKey('customization_origin', $executionItem);
            self::assertSame([
                'routine_identifier' => self::PARENT,
                'routine_name' => '親',
                'account_name' => '作者',
                'icon_image_url' => null,
            ], $routineItem['customization_origin']);
        }
    }
}
