<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RegisterListeningContentActionTest extends TestCase
{
    use RefreshDatabase;

    private const USER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

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

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/my/listening-contents', ['url' => 'https://example.com/audio'])->assertUnauthorized();
        $this->assertDatabaseCount('listening_contents', 0);
    }

    public function test_registers_multiple_urls_for_the_session_account_ignoring_spoofed_account(): void
    {
        $urls = ['https://open.spotify.com/track/example', 'https://music.apple.com/jp/album/example', 'spotify://track/123', 'https://open.spotify.com/track/example'];
        foreach ($urls as $url) {
            $this->withSession(['account_identifier' => self::USER])->postJson('/api/my/listening-contents', [
                'url' => $url,
                'account_identifier' => self::OTHER,
            ])->assertNoContent();
        }
        $this->assertDatabaseCount('listening_contents', 4);
        foreach (array_unique($urls) as $url) {
            $this->assertDatabaseHas('listening_contents', ['account_identifier' => self::USER, 'url' => $url]);
        }
        $this->assertDatabaseMissing('listening_contents', ['account_identifier' => self::OTHER]);
        $this->assertSame(2, DB::table('listening_contents')->where('account_identifier', self::USER)->where('url', $urls[0])->count());
    }

    public function test_another_account_can_register_the_same_url(): void
    {
        foreach ([self::USER, self::OTHER] as $account) {
            $this->withSession(['account_identifier' => $account])->postJson('/api/my/listening-contents', ['url' => 'https://example.com/audio'])->assertNoContent();
            $this->assertDatabaseHas('listening_contents', ['account_identifier' => $account, 'url' => 'https://example.com/audio']);
        }
        $this->assertDatabaseCount('listening_contents', 2);
    }

    public function test_rejects_missing_and_invalid_urls_without_saving(): void
    {
        foreach ([[], ['url' => ''], ['url' => 'not-a-url'], ['url' => 'https://'], ['url' => 123], ['url' => []]] as $payload) {
            $this->withSession(['account_identifier' => self::USER])->postJson('/api/my/listening-contents', $payload)->assertUnprocessable()->assertJsonValidationErrors('url');
        }
        $this->assertDatabaseCount('listening_contents', 0);
    }
}
