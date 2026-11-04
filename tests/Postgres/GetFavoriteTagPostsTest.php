<?php

declare(strict_types=1);

namespace Tests\Postgres;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsInput;
use Src\Account\Application\Usecase\Query\GetFavoriteTagPosts\GetFavoriteTagPostsInterface;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetFavoriteTagPostsTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    #[DataProvider('sessionTimeZones')]
    public function test_preserves_half_point_weights_and_truncates_past_and_future_hours_in_utc(string $timeZone): void
    {
        $this->travelTo(new CarbonImmutable('2001-01-01 00:00:00', 'UTC'));
        DB::select('select set_config(?, ?, true)', ['TimeZone', $timeZone]);
        $this->insertViewerAndFavoriteRoutine();
        $clock = DB::selectOne("select date_trunc('second', statement_timestamp() at time zone 'UTC') as anchor")->anchor;
        $anchor = new DateTimeImmutable($clock, new DateTimeZone('UTC'));
        $this->insertPost(1, $anchor->modify('+3610 seconds'));
        $this->insertPost(2, $anchor, 1);
        $this->insertPost(3, $anchor->modify('+3590 seconds'));
        $this->insertPost(4, $anchor->modify('-3590 seconds'));
        $this->insertPost(5, $anchor->modify('-3610 seconds'), 0, 1);
        $this->insertPost(6, $anchor->modify('-3610 seconds'));
        $query = $this->app->make(GetFavoriteTagPostsInterface::class);

        $result = $query->execute(new GetFavoriteTagPostsInput('11111111-1111-4111-8111-111111111111', 1, 20));

        self::assertSame(6, $result->total());
        self::assertSame(array_map($this->postIdentifier(...), [1, 2, 3, 4, 5, 6]), array_column($result->posts(), 'postIdentifier'));
        self::assertSame([0, 1, 0, 0, 0, 0], array_column($result->posts(), 'postLikeCount'));
        self::assertSame([0, 0, 0, 0, 1, 0], array_column($result->posts(), 'postSupportCount'));

        $page = $query->execute(new GetFavoriteTagPostsInput('11111111-1111-4111-8111-111111111111', 2, 2));

        self::assertSame(6, $page->total());
        self::assertSame(array_map($this->postIdentifier(...), [3, 4]), array_column($page->posts(), 'postIdentifier'));
    }

    public function test_uses_the_current_statement_clock_instead_of_the_transaction_or_php_clock(): void
    {
        $this->travelTo(new CarbonImmutable('2001-01-01 00:00:00', 'UTC'));
        $this->insertViewerAndFavoriteRoutine();
        $clock = DB::selectOne("select date_trunc('second', current_timestamp at time zone 'UTC') as anchor")->anchor;
        $anchor = new DateTimeImmutable($clock, new DateTimeZone('UTC'));
        $this->insertPost(1, $anchor->modify('-3599 seconds'));
        $this->insertPost(2, $anchor->modify('-3600 seconds'), 1);
        DB::select('select pg_sleep(2)');
        $elapsed = DB::selectOne('select extract(epoch from (statement_timestamp() - current_timestamp)) as seconds')->seconds;
        self::assertGreaterThanOrEqual(2, (float) $elapsed);

        $result = $this->app->make(GetFavoriteTagPostsInterface::class)
            ->execute(new GetFavoriteTagPostsInput('11111111-1111-4111-8111-111111111111', 1, 20));

        self::assertSame([$this->postIdentifier(2), $this->postIdentifier(1)], array_column($result->posts(), 'postIdentifier'));
    }

    /** @return array<string, array{string}> */
    public static function sessionTimeZones(): array
    {
        return [
            'UTC' => ['UTC'],
            'positive offset' => ['Asia/Tokyo'],
            'negative offset' => ['America/Los_Angeles'],
        ];
    }

    private function insertViewerAndFavoriteRoutine(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'account_name' => '推薦確認',
            'email_address' => 'postgres-fixture@example.invalid',
            'status' => 'active',
            'visibility' => 'public',
            'available' => true,
        ]);
        DB::table('routines')->insert([
            'routine_identifier' => '22222222-2222-4222-8222-222222222222',
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'routine_name' => '推薦確認',
        ]);
        DB::table('tags')->insert([
            'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'tag_name' => '推薦確認',
        ]);
        DB::table('favorite_tags')->insert([
            'account_identifier' => '11111111-1111-4111-8111-111111111111',
            'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]);
        DB::table('routine_tags')->insert([
            'routine_identifier' => '22222222-2222-4222-8222-222222222222',
            'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]);
    }

    private function insertPost(int $number, DateTimeImmutable $createdAt, int $likes = 0, int $supports = 0): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $this->postIdentifier($number),
            'routine_identifier' => '22222222-2222-4222-8222-222222222222',
            'post_category' => 'routine',
            'post_like_count' => $likes,
            'post_support_count' => $supports,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    private function postIdentifier(int $number): string
    {
        return sprintf('33333333-3333-4333-8333-%012d', $number);
    }
}
