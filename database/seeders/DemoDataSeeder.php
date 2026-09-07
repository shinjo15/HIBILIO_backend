<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        DB::transaction(function () use ($timestamp): void {
            $this->seedAccounts($timestamp);
            $this->seedTags($timestamp);
            $this->seedRoutines($timestamp);
            $this->seedRoutineActions($timestamp);
            $this->seedRoutineTags($timestamp);
            $this->seedPosts($timestamp);
            $this->seedFollows($timestamp);
            $this->seedLikes($timestamp);
            $this->seedSupports($timestamp);
        });
    }

    private function seedAccounts(mixed $timestamp): void
    {
        DB::table('accounts')->upsert([
            $this->account('10000000-0000-4000-8000-000000000001', 'UI確認ユーザー', 'ui-viewer@hibilio.local', $timestamp),
            $this->account('10000000-0000-4000-8000-000000000002', '春野あかり', 'akari@hibilio.local', $timestamp),
            $this->account('10000000-0000-4000-8000-000000000003', '蓮見そうた', 'sota@hibilio.local', $timestamp),
            $this->account('10000000-0000-4000-8000-000000000004', '美香', 'mika@hibilio.local', $timestamp),
        ], ['account_identifier'], ['account_name', 'account_bio', 'email_address', 'available', 'status', 'ban_until', 'updated_at']);
    }

    private function seedTags(mixed $timestamp): void
    {
        DB::table('tags')->upsert([
            $this->tag('20000000-0000-4000-8000-000000000001', '朝活', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000002', '集中', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000003', '運動', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000004', 'リラックス', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000005', '読書', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000006', '勉強', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000007', '瞑想', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000008', 'ストレッチ', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000009', '散歩', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000010', '睡眠', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000011', '食事', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000012', '料理', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000013', '掃除', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000014', '片付け', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000015', '家計管理', true, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000016', '日記', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000017', '写真', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000018', '音楽', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000019', '映画鑑賞', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000020', '植物の手入れ', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000021', '手帳', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000022', '語学', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000023', 'プログラミング', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000024', '創作', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000025', '絵を描く', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000026', '編み物', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000027', '旅行計画', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000028', '身だしなみ', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000029', 'スキンケア', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000030', '入浴', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000031', '水分補給', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000032', 'ランニング', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000033', '筋トレ', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000034', 'ヨガ', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000035', '自転車', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000036', '早起き', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000037', '振り返り', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000038', '目標設定', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000039', '休憩', false, $timestamp),
            $this->tag('20000000-0000-4000-8000-000000000040', '深呼吸', false, $timestamp),
        ], ['tag_identifier'], ['tag_name', 'available', 'pickup', 'updated_at']);
    }

    private function seedRoutines(mixed $timestamp): void
    {
        DB::table('routines')->upsert([
            $this->routine('30000000-0000-4000-8000-000000000001', null, '10000000-0000-4000-8000-000000000002', '朝の集中ルーティン', '気持ちよく一日を始めるためのルーティンです。', 35, $timestamp),
            $this->routine('30000000-0000-4000-8000-000000000002', null, '10000000-0000-4000-8000-000000000003', '午後の深呼吸リセット', '短い休憩で頭を切り替えます。', 15, $timestamp),
            $this->routine('30000000-0000-4000-8000-000000000003', null, '10000000-0000-4000-8000-000000000004', '夜のストレッチ', '眠る前に身体をゆるめます。', 20, $timestamp),
        ], ['routine_identifier'], ['parent_routine_identifier', 'routine_name', 'routine_memo', 'account_identifier', 'routine_execution_minutes', 'available', 'updated_at']);

        DB::table('routines')->upsert([
            $this->routine('30000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000004', '朝の集中ルーティンをカスタマイズ', '朝食前の短縮版です。', 20, $timestamp),
        ], ['routine_identifier'], ['parent_routine_identifier', 'routine_name', 'routine_memo', 'account_identifier', 'routine_execution_minutes', 'available', 'updated_at']);
    }

    private function seedRoutineActions(mixed $timestamp): void
    {
        DB::table('routine_actions')->upsert([
            $this->routineAction('40000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001', '水を飲む', 2, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000001', '今日の予定を書く', 8, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000001', '読書する', 25, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000002', '深呼吸する', 5, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000005', '30000000-0000-4000-8000-000000000002', '散歩する', 10, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000006', '30000000-0000-4000-8000-000000000003', '肩を回す', 10, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000007', '30000000-0000-4000-8000-000000000003', '前屈する', 10, $timestamp),
            $this->routineAction('40000000-0000-4000-8000-000000000008', '30000000-0000-4000-8000-000000000004', '朝日を浴びる', 20, $timestamp),
        ], ['routine_action_identifier'], ['parent_routine_action_identifier', 'routine_identifier', 'action_name', 'action_memo', 'action_minutes', 'available', 'updated_at']);
    }

    private function seedRoutineTags(mixed $timestamp): void
    {
        DB::table('routine_tags')->upsert([
            $this->routineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000002', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000002', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000003', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000004', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000001', $timestamp),
        ], ['routine_identifier', 'tag_identifier'], ['available', 'updated_at']);
    }

    private function seedPosts(mixed $timestamp): void
    {
        DB::table('posts')->upsert([
            $this->post('60000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001', 'routine', 2, 0, $timestamp),
            $this->post('60000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000001', 'action', 0, 1, $timestamp),
            $this->post('60000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000002', 'routine', 1, 0, $timestamp),
            $this->post('60000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000002', 'action', 0, 0, $timestamp),
            $this->post('60000000-0000-4000-8000-000000000005', '30000000-0000-4000-8000-000000000003', 'routine', 0, 0, $timestamp),
            $this->post('60000000-0000-4000-8000-000000000006', '30000000-0000-4000-8000-000000000004', 'routine', 0, 0, $timestamp),
        ], ['post_identifier'], ['routine_identifier', 'post_category', 'post_like_count', 'post_support_count', 'available', 'updated_at']);
    }

    private function seedFollows(mixed $timestamp): void
    {
        DB::table('follows')->upsert([
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000002', $timestamp),
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000003', $timestamp),
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000004', $timestamp),
        ], ['following_account_identifier', 'followed_account_identifier'], ['updated_at']);
    }

    private function seedLikes(mixed $timestamp): void
    {
        DB::table('likes')->upsert([
            $this->reaction('10000000-0000-4000-8000-000000000001', '60000000-0000-4000-8000-000000000001', $timestamp),
            $this->reaction('10000000-0000-4000-8000-000000000003', '60000000-0000-4000-8000-000000000001', $timestamp),
            $this->reaction('10000000-0000-4000-8000-000000000004', '60000000-0000-4000-8000-000000000003', $timestamp),
        ], ['account_identifier', 'post_identifier'], ['updated_at']);
    }

    private function seedSupports(mixed $timestamp): void
    {
        DB::table('supports')->upsert([
            $this->reaction('10000000-0000-4000-8000-000000000001', '60000000-0000-4000-8000-000000000002', $timestamp),
        ], ['account_identifier', 'post_identifier'], ['updated_at']);
    }

    /** @return array<string, mixed> */
    private function account(string $identifier, string $name, string $emailAddress, mixed $timestamp): array
    {
        return [
            'account_identifier' => $identifier,
            'account_name' => $name,
            'account_bio' => null,
            'email_address' => $emailAddress,
            'available' => true,
            'status' => 'active',
            'ban_until' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function tag(string $identifier, string $name, bool $pickup, mixed $timestamp): array
    {
        return [
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'pickup' => $pickup,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function routine(
        string $identifier,
        ?string $parentIdentifier,
        string $accountIdentifier,
        string $name,
        string $memo,
        int $executionMinutes,
        mixed $timestamp,
    ): array {
        return [
            'routine_identifier' => $identifier,
            'parent_routine_identifier' => $parentIdentifier,
            'routine_name' => $name,
            'routine_memo' => $memo,
            'account_identifier' => $accountIdentifier,
            'routine_execution_minutes' => $executionMinutes,
            'available' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function routineAction(string $identifier, string $routineIdentifier, string $name, int $minutes, mixed $timestamp): array
    {
        return [
            'routine_action_identifier' => $identifier,
            'parent_routine_action_identifier' => null,
            'routine_identifier' => $routineIdentifier,
            'action_name' => $name,
            'action_memo' => null,
            'action_minutes' => $minutes,
            'available' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function routineTag(string $routineIdentifier, string $tagIdentifier, mixed $timestamp): array
    {
        return [
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function post(
        string $identifier,
        string $routineIdentifier,
        string $category,
        int $likeCount,
        int $supportCount,
        mixed $timestamp,
    ): array {
        return [
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'post_category' => $category,
            'post_like_count' => $likeCount,
            'post_support_count' => $supportCount,
            'available' => true,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function follow(string $followingAccountIdentifier, string $followedAccountIdentifier, mixed $timestamp): array
    {
        return [
            'following_account_identifier' => $followingAccountIdentifier,
            'followed_account_identifier' => $followedAccountIdentifier,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /** @return array<string, mixed> */
    private function reaction(string $accountIdentifier, string $postIdentifier, mixed $timestamp): array
    {
        return [
            'account_identifier' => $accountIdentifier,
            'post_identifier' => $postIdentifier,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
