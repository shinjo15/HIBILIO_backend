<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_idempotent_large_demo_data_with_consistent_relations_and_post_counts(): void
    {
        $this->assertTrue(class_exists(DemoDataSeeder::class));

        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        $this->assertSame(500, DB::table('accounts')->count());
        $this->assertSame(499, DB::table('follows')->count());
        $this->assertSame(40, DB::table('tags')->count());
        $this->assertSame(4, DB::table('routines')->count());
        $this->assertSame(8, DB::table('routine_actions')->count());
        $this->assertSame(1_500, DB::table('routine_executions')->count());
        $this->assertSame(1_500, DB::table('routine_execution_actions')->count());
        $this->assertSame(1_000, DB::table('posts')->where('post_category', 'routine')->count());
        $this->assertSame(1_502, DB::table('posts')->where('post_category', 'action')->count());
        $this->assertSame(2_502, DB::table('posts')->count());
        $this->assertSame(1_503, DB::table('likes')->count());
        $this->assertSame(1_501, DB::table('supports')->count());

        $this->assertSame(0, DB::table('posts')
            ->where('post_category', 'action')
            ->whereNotNull('routine_execution_identifier')
            ->select('routine_execution_identifier')
            ->groupBy('routine_execution_identifier')
            ->havingRaw('COUNT(*) != 1')
            ->count());
        $this->assertSame(0, DB::table('routine_executions')
            ->leftJoin('posts', static fn ($join) => $join
                ->on('posts.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
                ->where('posts.post_category', 'action'))
            ->whereNull('posts.post_identifier')
            ->count());
        $this->assertSame(0, DB::table('routine_executions')
            ->leftJoin('routine_execution_actions', 'routine_execution_actions.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->select('routine_executions.routine_execution_identifier')
            ->groupBy('routine_executions.routine_execution_identifier')
            ->havingRaw('COUNT(routine_execution_actions.routine_action_identifier) < 1')
            ->count());
        $this->assertSame(0, DB::table('routine_executions')
            ->leftJoin('routine_execution_actions', 'routine_execution_actions.routine_execution_identifier', '=', 'routine_executions.routine_execution_identifier')
            ->whereNull('routine_execution_actions.routine_action_identifier')
            ->count());
        $this->assertSame(DB::table('routine_execution_actions')->count(), DB::table('routine_execution_actions')
            ->join('routine_executions', 'routine_executions.routine_execution_identifier', '=', 'routine_execution_actions.routine_execution_identifier')
            ->join('routine_actions', 'routine_actions.routine_action_identifier', '=', 'routine_execution_actions.routine_action_identifier')
            ->whereColumn('routine_actions.routine_identifier', 'routine_executions.routine_identifier')
            ->count());

        $this->assertSame(0, DB::table('likes')
            ->select(['account_identifier', 'post_identifier'])
            ->groupBy(['account_identifier', 'post_identifier'])
            ->havingRaw('COUNT(*) > 1')
            ->count());
        $this->assertSame(0, DB::table('supports')
            ->select(['account_identifier', 'post_identifier'])
            ->groupBy(['account_identifier', 'post_identifier'])
            ->havingRaw('COUNT(*) > 1')
            ->count());
        $this->assertSame(0, DB::table('follows')
            ->select(['following_account_identifier', 'followed_account_identifier'])
            ->groupBy(['following_account_identifier', 'followed_account_identifier'])
            ->havingRaw('COUNT(*) > 1')
            ->count());
        $this->assertSame(0, DB::table('posts')
            ->leftJoin('routines', 'routines.routine_identifier', '=', 'posts.routine_identifier')
            ->whereNull('routines.routine_identifier')
            ->count());
        $this->assertSame(0, DB::table('routine_executions')
            ->leftJoin('accounts', 'accounts.account_identifier', '=', 'routine_executions.executor_account_identifier')
            ->leftJoin('routines', 'routines.routine_identifier', '=', 'routine_executions.routine_identifier')
            ->where(static fn ($query) => $query
                ->whereNull('accounts.account_identifier')
                ->orWhereNull('routines.routine_identifier'))
            ->count());

        $this->assertSame(0, DB::table('posts')
            ->leftJoin('likes', 'likes.post_identifier', '=', 'posts.post_identifier')
            ->select('posts.post_identifier')
            ->selectRaw('posts.post_like_count, COUNT(likes.post_identifier) AS like_count')
            ->groupBy(['posts.post_identifier', 'posts.post_like_count'])
            ->havingRaw('posts.post_like_count != COUNT(likes.post_identifier)')
            ->count());
        $this->assertSame(0, DB::table('posts')
            ->leftJoin('supports', 'supports.post_identifier', '=', 'posts.post_identifier')
            ->select('posts.post_identifier')
            ->selectRaw('posts.post_support_count, COUNT(supports.post_identifier) AS support_count')
            ->groupBy(['posts.post_identifier', 'posts.post_support_count'])
            ->havingRaw('posts.post_support_count != COUNT(supports.post_identifier)')
            ->count());

        $this->assertDatabaseHas('accounts', [
            'account_identifier' => '10000000-0000-4000-8000-000000000001',
            'email_address' => 'ui-viewer@hibilio.local',
            'account_name' => 'UI確認ユーザー',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('follows', [
            'following_account_identifier' => '10000000-0000-4000-8000-000000000001',
            'followed_account_identifier' => '10000000-0000-4000-8000-000000000002',
        ]);
        $this->assertDatabaseHas('routines', [
            'routine_identifier' => '30000000-0000-4000-8000-000000000004',
            'parent_routine_identifier' => '30000000-0000-4000-8000-000000000001',
            'routine_name' => '朝の集中ルーティンをカスタマイズ',
        ]);
        foreach ([
            ['20000000-0000-4000-8000-000000000001', '朝活', true],
            ['20000000-0000-4000-8000-000000000002', '集中', true],
            ['20000000-0000-4000-8000-000000000003', '運動', true],
            ['20000000-0000-4000-8000-000000000004', 'リラックス', true],
            ['20000000-0000-4000-8000-000000000005', '読書', true],
            ['20000000-0000-4000-8000-000000000006', '勉強', true],
            ['20000000-0000-4000-8000-000000000007', '瞑想', true],
            ['20000000-0000-4000-8000-000000000008', 'ストレッチ', true],
            ['20000000-0000-4000-8000-000000000009', '散歩', true],
            ['20000000-0000-4000-8000-000000000010', '睡眠', true],
            ['20000000-0000-4000-8000-000000000011', '食事', true],
            ['20000000-0000-4000-8000-000000000012', '料理', true],
            ['20000000-0000-4000-8000-000000000013', '掃除', true],
            ['20000000-0000-4000-8000-000000000014', '片付け', true],
            ['20000000-0000-4000-8000-000000000015', '家計管理', true],
            ['20000000-0000-4000-8000-000000000016', '日記', false],
            ['20000000-0000-4000-8000-000000000017', '写真', false],
            ['20000000-0000-4000-8000-000000000018', '音楽', false],
            ['20000000-0000-4000-8000-000000000019', '映画鑑賞', false],
            ['20000000-0000-4000-8000-000000000020', '植物の手入れ', false],
            ['20000000-0000-4000-8000-000000000021', '手帳', false],
            ['20000000-0000-4000-8000-000000000022', '語学', false],
            ['20000000-0000-4000-8000-000000000023', 'プログラミング', false],
            ['20000000-0000-4000-8000-000000000024', '創作', false],
            ['20000000-0000-4000-8000-000000000025', '絵を描く', false],
            ['20000000-0000-4000-8000-000000000026', '編み物', false],
            ['20000000-0000-4000-8000-000000000027', '旅行計画', false],
            ['20000000-0000-4000-8000-000000000028', '身だしなみ', false],
            ['20000000-0000-4000-8000-000000000029', 'スキンケア', false],
            ['20000000-0000-4000-8000-000000000030', '入浴', false],
            ['20000000-0000-4000-8000-000000000031', '水分補給', false],
            ['20000000-0000-4000-8000-000000000032', 'ランニング', false],
            ['20000000-0000-4000-8000-000000000033', '筋トレ', false],
            ['20000000-0000-4000-8000-000000000034', 'ヨガ', false],
            ['20000000-0000-4000-8000-000000000035', '自転車', false],
            ['20000000-0000-4000-8000-000000000036', '早起き', false],
            ['20000000-0000-4000-8000-000000000037', '振り返り', false],
            ['20000000-0000-4000-8000-000000000038', '目標設定', false],
            ['20000000-0000-4000-8000-000000000039', '休憩', false],
            ['20000000-0000-4000-8000-000000000040', '深呼吸', false],
        ] as [$identifier, $name, $pickup]) {
            $this->assertDatabaseHas('tags', [
                'tag_identifier' => $identifier,
                'tag_name' => $name,
                'pickup' => $pickup,
            ]);
        }
        $this->assertSame(6, DB::table('routine_tags')->count());
        foreach ([
            ['30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001'],
            ['30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000002'],
            ['30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000002'],
            ['30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000003'],
            ['30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000004'],
            ['30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000001'],
        ] as [$routineIdentifier, $tagIdentifier]) {
            $this->assertDatabaseHas('routine_tags', [
                'routine_identifier' => $routineIdentifier,
                'tag_identifier' => $tagIdentifier,
            ]);
        }
        $this->assertDatabaseHas('posts', [
            'post_identifier' => '60000000-0000-4000-8000-000000000001',
            'post_category' => 'routine',
            'post_like_count' => 2,
        ]);
        $this->assertDatabaseHas('posts', [
            'post_identifier' => '60000000-0000-4000-8000-000000000002',
            'post_category' => 'action',
            'post_support_count' => 1,
        ]);
    }
}
