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

    public function test_seeds_idempotent_demo_data_for_following_posts_screen(): void
    {
        $this->assertTrue(class_exists(DemoDataSeeder::class));

        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        $this->assertSame(4, DB::table('accounts')->count());
        $this->assertSame(3, DB::table('follows')->count());
        $this->assertSame(40, DB::table('tags')->count());
        $this->assertSame(4, DB::table('routines')->count());
        $this->assertSame(8, DB::table('routine_actions')->count());
        $this->assertSame(6, DB::table('posts')->count());
        $this->assertSame(3, DB::table('likes')->count());

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
