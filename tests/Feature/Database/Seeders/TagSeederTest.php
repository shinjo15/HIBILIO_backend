<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class TagSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_exactly_the_approved_tags_without_other_table_writes(): void
    {
        $connection = DB::connection();
        $connection->enableQueryLog();

        $this->assertSame(0, Artisan::call('db:seed', ['--class' => 'TagSeeder', '--force' => true]));

        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();
        $this->assertSame(40, DB::table('tags')->count());
        foreach ([
            '朝活', '集中', '運動', 'リラックス', '読書', '勉強', '瞑想', 'ストレッチ',
            '散歩', '睡眠', '食事', '料理', '掃除', '片付け', '家計管理', '日記',
            '写真', '音楽', '映画鑑賞', '植物の手入れ', '手帳', '語学', 'プログラミング',
            '創作', '絵を描く', '編み物', '旅行計画', '身だしなみ', 'スキンケア', '入浴',
            '水分補給', 'ランニング', '筋トレ', 'ヨガ', '自転車', '早起き', '振り返り',
            '目標設定', '休憩', '深呼吸',
        ] as $index => $name) {
            $this->assertDatabaseHas('tags', [
                'tag_identifier' => sprintf('20000000-0000-4000-8000-%012d', $index + 1),
                'tag_name' => $name,
                'available' => true,
                'pickup' => $index < 15,
            ]);
        }
        foreach ($queries as $query) {
            if (preg_match('/^\s*(insert|update|delete|replace|truncate)\b/i', $query['query'])) {
                $this->assertMatchesRegularExpression('/^insert into ["`]tags["`] /i', $query['query']);
            }
        }
        foreach (['users', 'accounts', 'posts', 'routines', 'routine_tags'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_reexecution_preserves_every_column_including_timestamps(): void
    {
        $this->travelTo(now()->startOfSecond());
        Artisan::call('db:seed', ['--class' => 'TagSeeder']);
        $before = DB::table('tags')->orderBy('tag_identifier')->get()->all();

        $this->travel(1)->days();
        Artisan::call('db:seed', ['--class' => 'TagSeeder']);

        $this->assertEquals($before, DB::table('tags')->orderBy('tag_identifier')->get()->all());
        $this->assertDatabaseCount('tags', 40);
    }

    public function test_preserves_modified_existing_and_unrelated_tags_while_adding_missing_tags(): void
    {
        $existing = [
            'tag_identifier' => '20000000-0000-4000-8000-000000000001',
            'tag_name' => '変更済み',
            'available' => false,
            'pickup' => false,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
        ];
        $unrelated = array_replace($existing, [
            'tag_identifier' => '90000000-0000-4000-8000-000000000001',
            'tag_name' => '独自タグ',
            'pickup' => true,
        ]);
        DB::table('tags')->insert([$existing, $unrelated]);
        $before = DB::table('tags')->orderBy('tag_identifier')->get()->all();

        Artisan::call('db:seed', ['--class' => 'TagSeeder']);

        $this->assertDatabaseCount('tags', 41);
        $this->assertEquals($before, DB::table('tags')->whereIn('tag_identifier', [
            $existing['tag_identifier'], $unrelated['tag_identifier'],
        ])->orderBy('tag_identifier')->get()->all());
        $this->assertDatabaseHas('tags', ['tag_name' => '深呼吸', 'available' => true, 'pickup' => false]);
    }

    #[DataProvider('conflictingIdentifiers')]
    public function test_name_collision_rolls_back_all_new_tags_and_preserves_existing_rows(string $identifier): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => '深呼吸',
            'available' => false,
            'pickup' => true,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
        ]);
        $before = DB::table('tags')->get()->all();

        try {
            Artisan::call('db:seed', ['--class' => 'TagSeeder']);
            $this->fail('A tag name owned by another identifier must not be duplicated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Initial tag name conflicts with an existing tag: 20000000-0000-4000-8000-000000000040', $exception->getMessage());
        }

        $this->assertEquals($before, DB::table('tags')->get()->all());
    }

    /** @return array<string, array{string}> */
    public static function conflictingIdentifiers(): array
    {
        return [
            'unrelated identifier' => ['90000000-0000-4000-8000-000000000001'],
            'renamed initial tag' => ['20000000-0000-4000-8000-000000000001'],
        ];
    }
}
