<?php

declare(strict_types=1);

namespace Tests\Feature\ListeningContent\Presentation;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInput;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInterface;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsOutputPort;
use Tests\TestCase;

final class GetListeningContentsActionTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const MISSING = '33333333-3333-4333-8333-333333333333';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') !== 'sqlite' || $app['config']->get('database.connections.sqlite.database') !== ':memory:' || $app['config']->get('database.connections.sqlite.url')) {
            throw new \RuntimeException('Tests require an isolated in-memory SQLite database.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('accounts')->insert([
            ['account_identifier' => self::USER, 'account_name' => 'User', 'email_address' => 'user@example.com'],
            ['account_identifier' => self::OTHER, 'account_name' => 'Other', 'email_address' => 'other@example.com'],
        ]);
    }

    public function test_my_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/my/listening-contents')->assertUnauthorized();
    }

    public function test_query_returns_output_port_or_null_for_missing_account(): void
    {
        $query = $this->app->make(GetAccountListeningContentsInterface::class);
        $output = $query->execute(new GetAccountListeningContentsInput(self::USER, null));

        $this->assertInstanceOf(GetAccountListeningContentsOutputPort::class, $output);
        $this->assertSame([], $output->items());

        DB::table('listening_contents')->insert(['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::USER, 'url' => 'https://example.com/content']);
        $output = $query->execute(new GetAccountListeningContentsInput(self::USER, self::OTHER));
        $this->assertInstanceOf(GetAccountListeningContentsOutputPort::class, $output);
        $this->assertSame([['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'url' => 'https://example.com/content']], $output->items());

        DB::table('accounts')->where('account_identifier', self::USER)->update(['visibility' => 'private']);
        $output = $query->execute(new GetAccountListeningContentsInput(self::USER, self::OTHER));
        $this->assertInstanceOf(GetAccountListeningContentsOutputPort::class, $output);
        $this->assertSame([], $output->items());
        $this->assertNull($query->execute(new GetAccountListeningContentsInput(self::MISSING, null)));
    }

    public function test_lists_distinct_registrations_and_isolates_accounts(): void
    {
        DB::table('listening_contents')->insert([
            ['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::USER, 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'account_identifier' => self::USER, 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/other'],
        ]);
        $mine = ['items' => [
            ['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'url' => 'https://example.com/same'],
        ]];
        $other = ['items' => [['listening_content_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'url' => 'https://example.com/other']]];
        $this->withSession(['account_identifier' => self::USER])->getJson('/api/my/listening-contents')->assertOk()->assertExactJson($mine);
        $this->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertOk()->assertExactJson($other);
        $this->getJson('/api/accounts/'.self::USER.'/listening-contents')->assertOk()->assertExactJson($mine);
    }

    public function test_empty_accounts_and_public_anonymous_access(): void
    {
        $this->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertOk()->assertExactJson(['items' => []]);
        $this->withSession(['account_identifier' => self::USER])->getJson('/api/my/listening-contents')->assertOk()->assertExactJson(['items' => []]);
    }

    public function test_missing_or_unavailable_account_is_not_found(): void
    {
        $this->getJson('/api/accounts/'.self::MISSING.'/listening-contents')->assertNotFound();
        foreach ([['available' => false], ['available' => true, 'status' => 'temporarily_banned'], ['available' => true, 'status' => 'permanently_banned']] as $state) {
            DB::table('accounts')->where('account_identifier', self::OTHER)->update($state);
            $this->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertNotFound();
        }
    }

    public function test_private_account_contents_are_hidden_except_from_owner_and_follower(): void
    {
        DB::table('accounts')->where('account_identifier', self::OTHER)->update(['visibility' => 'private']);
        DB::table('listening_contents')->insert(['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/private']);
        $url = '/api/accounts/'.self::OTHER.'/listening-contents';
        $this->getJson($url)->assertOk()->assertExactJson(['items' => []]);
        $this->withSession(['account_identifier' => self::USER])->getJson($url)->assertOk()->assertExactJson(['items' => []]);
        DB::table('follows')->insert(['following_account_identifier' => self::OTHER, 'followed_account_identifier' => self::USER]);
        $this->getJson($url)->assertOk()->assertExactJson(['items' => []]);
        DB::table('follows')->insert(['following_account_identifier' => self::USER, 'followed_account_identifier' => self::OTHER]);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'items');
        $this->withSession(['account_identifier' => self::OTHER])->getJson('/api/my/listening-contents')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'items');
    }

    public function test_block_in_either_direction_hides_the_target(): void
    {
        DB::table('listening_contents')->insert(['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/other']);
        DB::table('follows')->insert(['following_account_identifier' => self::USER, 'followed_account_identifier' => self::OTHER]);
        DB::table('accounts')->where('account_identifier', self::OTHER)->update(['visibility' => 'private']);
        foreach ([[self::USER, self::OTHER], [self::OTHER, self::USER]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->withSession(['account_identifier' => self::USER])->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertNotFound();
            DB::table('blocks')->delete();
        }
    }

    public function test_changes_after_access_check_cannot_expose_contents(): void
    {
        DB::table('listening_contents')->insert(['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/other']);
        $change = null;
        $changed = false;
        DB::listen(static function (QueryExecuted $query) use (&$change, &$changed): void {
            if ($change === null || ! str_contains($query->sql, 'select exists') || ! str_contains($query->sql, 'accounts') || ! str_contains($query->sql, 'visibility')) {
                return;
            }
            $apply = $change;
            $change = null;
            $changed = true;
            $apply();
        });

        $changes = [
            static fn () => DB::table('accounts')->where('account_identifier', self::OTHER)->update(['visibility' => 'private']),
            static fn () => DB::table('accounts')->where('account_identifier', self::OTHER)->update(['available' => false]),
            static fn () => DB::table('accounts')->where('account_identifier', self::OTHER)->update(['status' => 'temporarily_banned']),
            static fn () => DB::table('blocks')->insert(['blocking_account_identifier' => self::USER, 'blocked_account_identifier' => self::OTHER]),
            static fn () => DB::table('blocks')->insert(['blocking_account_identifier' => self::OTHER, 'blocked_account_identifier' => self::USER]),
        ];
        foreach ($changes as $apply) {
            DB::table('accounts')->where('account_identifier', self::OTHER)->update(['visibility' => 'public', 'available' => true, 'status' => 'active']);
            DB::table('blocks')->delete();
            $changed = false;
            $change = $apply;
            $this->withSession(['account_identifier' => self::USER])->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertOk()->assertExactJson(['items' => []]);
            $this->assertTrue($changed, 'The access check must complete before permissions change.');
        }
    }

    public function test_invalid_account_identifier_is_rejected_before_querying_accounts(): void
    {
        $queried = false;
        DB::listen(static function (QueryExecuted $query) use (&$queried): void {
            if (str_contains($query->sql, 'accounts')) {
                $queried = true;
            }
        });
        $this->getJson('/api/accounts/not-a-uuid/listening-contents')->assertNotFound();
        $this->assertFalse($queried);
    }

    public function test_anonymous_viewer_gets_all_public_contents_including_duplicate_urls(): void
    {
        DB::table('listening_contents')->insert([
            ['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'account_identifier' => self::USER, 'url' => 'https://example.com/other'],
        ]);
        $this->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertOk()->assertExactJson(['items' => [
            ['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'url' => 'https://example.com/same'],
            ['listening_content_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'url' => 'https://example.com/same'],
        ]]);
    }

    public function test_pending_follow_request_does_not_grant_private_access(): void
    {
        DB::table('accounts')->where('account_identifier', self::OTHER)->update(['visibility' => 'private']);
        DB::table('listening_contents')->insert(['listening_content_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => self::OTHER, 'url' => 'https://example.com/private']);
        DB::table('follow_requests')->insert(['requesting_account_identifier' => self::USER, 'target_account_identifier' => self::OTHER, 'status' => 'pending']);
        $this->withSession(['account_identifier' => self::USER])->getJson('/api/accounts/'.self::OTHER.'/listening-contents')->assertOk()->assertExactJson(['items' => []]);
    }

    public function test_invalid_or_missing_account_session_is_unauthenticated(): void
    {
        foreach ([[], '', self::MISSING] as $identifier) {
            $this->withSession(['account_identifier' => $identifier])->getJson('/api/my/listening-contents')->assertUnauthorized();
        }
    }
}
