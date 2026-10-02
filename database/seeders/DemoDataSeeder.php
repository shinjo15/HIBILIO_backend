<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoDataSeeder extends Seeder
{
    private const int CHUNK_SIZE = 250;

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
            $this->seedRoutineExecutions($timestamp);
            $this->seedRoutineExecutionPosts($timestamp);
            $this->seedFollows($timestamp);
            $this->seedLikes($timestamp);
            $this->seedSupports($timestamp);
            $this->refreshPostReactionCounts();
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

        $accounts = [];

        for ($number = 5; $number <= 250; $number++) {
            $accounts[] = $this->account(
                $this->accountIdentifier($number),
                sprintf('デモユーザー%03d', $number),
                sprintf('demo-user-%03d@hibilio.local', $number),
                $timestamp,
            );
            $this->upsertChunk('accounts', $accounts, ['account_identifier'], ['account_name', 'account_bio', 'email_address', 'available', 'status', 'ban_until', 'updated_at']);
        }

        $this->upsertChunk('accounts', $accounts, ['account_identifier'], ['account_name', 'account_bio', 'email_address', 'available', 'status', 'ban_until', 'updated_at'], true);
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

        $routines = [];

        for ($number = 5; $number <= 500; $number++) {
            $template = $this->routineTemplateForRoutineNumber($number);
            $routines[] = $this->routine(
                $this->routineIdentifier($number),
                null,
                $this->accountIdentifier((($number - 1) % 250) + 1),
                $template['name'],
                $template['memo'],
                array_sum(array_column($template['actions'], 'minutes')),
                $timestamp,
            );
            $this->upsertChunk('routines', $routines, ['routine_identifier'], ['parent_routine_identifier', 'routine_name', 'routine_memo', 'account_identifier', 'routine_execution_minutes', 'available', 'updated_at']);
        }

        $this->upsertChunk('routines', $routines, ['routine_identifier'], ['parent_routine_identifier', 'routine_name', 'routine_memo', 'account_identifier', 'routine_execution_minutes', 'available', 'updated_at'], true);
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
            $this->routineAction('40000000-0000-4000-8000-000000000008', '30000000-0000-4000-8000-000000000004', '朝日を浴びる', 10, $timestamp),
        ], ['routine_action_identifier'], ['parent_routine_action_identifier', 'routine_identifier', 'action_name', 'action_memo', 'action_minutes', 'available', 'updated_at']);

        $actions = [
            $this->routineAction($this->identifier('40000000', 11), '30000000-0000-4000-8000-000000000004', '軽くストレッチする', 10, $timestamp),
        ];

        for ($number = 5; $number <= 500; $number++) {
            foreach ($this->routineTemplateForRoutineNumber($number)['actions'] as $position => $action) {
                $actions[] = $this->routineAction(
                    $this->routineActionIdentifier($number, $position + 1),
                    $this->routineIdentifier($number),
                    $action['name'],
                    $action['minutes'],
                    $timestamp,
                );
                $this->upsertChunk('routine_actions', $actions, ['routine_action_identifier'], ['parent_routine_action_identifier', 'routine_identifier', 'action_name', 'action_memo', 'action_minutes', 'available', 'updated_at']);
            }
        }

        $this->upsertChunk('routine_actions', $actions, ['routine_action_identifier'], ['parent_routine_action_identifier', 'routine_identifier', 'action_name', 'action_memo', 'action_minutes', 'available', 'updated_at'], true);
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
            $this->routineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000040', $timestamp),
            $this->routineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000005', $timestamp),
        ], ['routine_identifier', 'tag_identifier'], ['available', 'updated_at']);

        $routineTags = [];

        for ($number = 5; $number <= 500; $number++) {
            foreach ($this->routineTemplateForRoutineNumber($number)['tagNumbers'] as $tagNumber) {
                $routineTags[] = $this->routineTag($this->routineIdentifier($number), $this->identifier('20000000', $tagNumber), $timestamp);
            }
            $this->upsertChunk('routine_tags', $routineTags, ['routine_identifier', 'tag_identifier'], ['available', 'updated_at']);
        }

        $this->upsertChunk('routine_tags', $routineTags, ['routine_identifier', 'tag_identifier'], ['available', 'updated_at'], true);
    }

    private function seedPosts(mixed $timestamp): void
    {
        $posts = [];

        for ($number = 1; $number <= 500; $number++) {
            $posts[] = $this->post(
                $number === 1 ? $this->identifier('60000000', 1) : $this->identifier('61000000', $number),
                $this->routineIdentifier($number),
                'routine',
                0,
                0,
                $timestamp,
            );
            $this->upsertChunk('posts', $posts, ['post_identifier'], ['routine_identifier', 'routine_execution_identifier', 'post_category', 'post_like_count', 'post_support_count', 'available', 'updated_at']);
        }

        $this->upsertChunk('posts', $posts, ['post_identifier'], ['routine_identifier', 'routine_execution_identifier', 'post_category', 'post_like_count', 'post_support_count', 'available', 'updated_at'], true);
    }

    private function seedRoutineExecutions(mixed $timestamp): void
    {
        $executions = [];
        $executionActions = [];

        for ($number = 1; $number <= 700; $number++) {
            $routineNumber = (($number - 1) % 500) + 1;
            $routineIdentifier = $this->routineIdentifier($routineNumber);
            $executionIdentifier = $this->identifier('70000000', $number);
            $executions[] = [
                'routine_execution_identifier' => $executionIdentifier,
                'executor_account_identifier' => $this->accountIdentifier((($number - 1) % 250) + 1),
                'routine_identifier' => $routineIdentifier,
                'executed_at' => $timestamp,
                'routine_execution_memo' => '一つずつ行動を終えました。',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            $executionActions[] = [
                'routine_execution_identifier' => $executionIdentifier,
                'routine_action_identifier' => $this->routineActionIdentifier($routineNumber, 1),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            $executionActions[] = [
                'routine_execution_identifier' => $executionIdentifier,
                'routine_action_identifier' => $this->routineActionIdentifier($routineNumber, 2),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->upsertChunk('routine_executions', $executions, ['routine_execution_identifier'], ['executor_account_identifier', 'routine_identifier', 'executed_at', 'routine_execution_memo', 'updated_at'], true);
        $this->upsertChunk('routine_execution_actions', $executionActions, ['routine_execution_identifier', 'routine_action_identifier'], ['updated_at'], true);
    }

    private function seedRoutineExecutionPosts(mixed $timestamp): void
    {
        $posts = [];

        for ($number = 1; $number <= 700; $number++) {
            $posts[] = $this->post(
                $number === 1 ? $this->identifier('60000000', 2) : $this->identifier('62000000', $number),
                $this->routineIdentifier((($number - 1) % 500) + 1),
                'action',
                0,
                0,
                $timestamp,
                $this->identifier('70000000', $number),
            );
            $this->upsertChunk('posts', $posts, ['post_identifier'], ['routine_identifier', 'routine_execution_identifier', 'post_category', 'post_like_count', 'post_support_count', 'available', 'updated_at']);
        }

        $this->upsertChunk('posts', $posts, ['post_identifier'], ['routine_identifier', 'routine_execution_identifier', 'post_category', 'post_like_count', 'post_support_count', 'available', 'updated_at'], true);
    }

    private function seedFollows(mixed $timestamp): void
    {
        DB::table('follows')->upsert([
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000002', $timestamp),
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000003', $timestamp),
            $this->follow('10000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000004', $timestamp),
        ], ['following_account_identifier', 'followed_account_identifier'], ['updated_at']);

        $follows = [];

        for ($number = 5; $number <= 250; $number++) {
            $follows[] = $this->follow(
                $this->accountIdentifier($number),
                $this->accountIdentifier($number === 250 ? 1 : $number + 1),
                $timestamp,
            );
            $this->upsertChunk('follows', $follows, ['following_account_identifier', 'followed_account_identifier'], ['updated_at']);
        }

        $this->upsertChunk('follows', $follows, ['following_account_identifier', 'followed_account_identifier'], ['updated_at'], true);
    }

    private function seedLikes(mixed $timestamp): void
    {
        DB::table('likes')->upsert([
            $this->reaction('10000000-0000-4000-8000-000000000001', '60000000-0000-4000-8000-000000000001', $timestamp),
            $this->reaction('10000000-0000-4000-8000-000000000003', '60000000-0000-4000-8000-000000000001', $timestamp),
            $this->reaction($this->accountIdentifier(5), '60000000-0000-4000-8000-000000000002', $timestamp),
        ], ['account_identifier', 'post_identifier'], ['updated_at']);

        $likes = [];

        for ($number = 2; $number <= 700; $number++) {
            $likes[] = $this->reaction($this->accountIdentifier((($number - 1) % 250) + 1), $this->identifier('62000000', $number), $timestamp);
            $this->upsertChunk('likes', $likes, ['account_identifier', 'post_identifier'], ['updated_at']);
        }

        $this->upsertChunk('likes', $likes, ['account_identifier', 'post_identifier'], ['updated_at'], true);
    }

    private function seedSupports(mixed $timestamp): void
    {
        DB::table('supports')->upsert([
            $this->reaction('10000000-0000-4000-8000-000000000001', '60000000-0000-4000-8000-000000000002', $timestamp),
            $this->reaction($this->accountIdentifier(6), '61000000-0000-4000-8000-000000000002', $timestamp),
        ], ['account_identifier', 'post_identifier'], ['updated_at']);

        $supports = [];

        for ($number = 2; $number <= 700; $number++) {
            $supports[] = $this->reaction($this->accountIdentifier((($number - 1) % 250) + 1), $this->identifier('62000000', $number), $timestamp);
            $this->upsertChunk('supports', $supports, ['account_identifier', 'post_identifier'], ['updated_at']);
        }

        $this->upsertChunk('supports', $supports, ['account_identifier', 'post_identifier'], ['updated_at'], true);
    }

    private function refreshPostReactionCounts(): void
    {
        $postIdentifiers = $this->seededPostIdentifiers();

        DB::table('posts')
            ->whereIn('post_identifier', $postIdentifiers)
            ->update([
                'post_like_count' => 0,
                'post_support_count' => 0,
            ]);

        $this->refreshPostReactionCount('likes', 'post_like_count', $postIdentifiers);
        $this->refreshPostReactionCount('supports', 'post_support_count', $postIdentifiers);
    }

    /** @param list<string> $postIdentifiers */
    private function refreshPostReactionCount(string $reactionTable, string $countColumn, array $postIdentifiers): void
    {
        foreach (DB::table($reactionTable)
            ->whereIn('post_identifier', $postIdentifiers)
            ->select('post_identifier')
            ->selectRaw('COUNT(*) AS reaction_count')
            ->groupBy('post_identifier')
            ->get() as $reaction) {
            DB::table('posts')
                ->where('post_identifier', $reaction->post_identifier)
                ->update([$countColumn => $reaction->reaction_count]);
        }
    }

    /** @return list<string> */
    private function seededPostIdentifiers(): array
    {
        $postIdentifiers = [
            $this->identifier('60000000', 1),
            $this->identifier('60000000', 2),
        ];

        for ($number = 2; $number <= 500; $number++) {
            $postIdentifiers[] = $this->identifier('61000000', $number);
        }

        for ($number = 2; $number <= 700; $number++) {
            $postIdentifiers[] = $this->identifier('62000000', $number);
        }

        return $postIdentifiers;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $uniqueBy
     * @param  array<int, string>  $update
     */
    private function upsertChunk(string $table, array &$rows, array $uniqueBy, array $update, bool $force = false): void
    {
        if ($rows === [] || (count($rows) < self::CHUNK_SIZE && ! $force)) {
            return;
        }

        DB::table($table)->upsert($rows, $uniqueBy, $update);
        $rows = [];
    }

    private function identifier(string $prefix, int $number): string
    {
        return sprintf('%s-0000-4000-8000-%012d', $prefix, $number);
    }

    private function accountIdentifier(int $number): string
    {
        return $number <= 4
            ? $this->identifier('10000000', $number)
            : $this->identifier('11000000', $number);
    }

    private function routineIdentifier(int $number): string
    {
        return $number <= 4
            ? $this->identifier('30000000', $number)
            : $this->identifier('31000000', $number);
    }

    private function routineActionIdentifier(int $routineNumber, int $position): string
    {
        $identifiers = match ($routineNumber) {
            1 => [
                $this->identifier('40000000', 1),
                $this->identifier('40000000', 2),
                $this->identifier('40000000', 3),
            ],
            2 => [
                $this->identifier('40000000', 4),
                $this->identifier('40000000', 5),
            ],
            3 => [
                $this->identifier('40000000', 6),
                $this->identifier('40000000', 7),
            ],
            4 => [
                $this->identifier('40000000', 8),
                $this->identifier('40000000', 11),
            ],
            default => [],
        };

        return $routineNumber <= 4
            ? $identifiers[$position - 1]
            : $this->identifier('41000000', (($routineNumber - 5) * 3) + $position);
    }

    /** @return array{name: string, memo: string, tagNumbers: list<int>, actions: list<array{name: string, minutes: int}>} */
    private function routineTemplate(int $number): array
    {
        $templates = [
            ['name' => '就寝前の睡眠準備', 'memo' => '明日の準備を済ませて、眠る前の刺激を減らすルーティンです。', 'tagNumbers' => [10, 30], 'actions' => [['name' => 'スマートフォンを充電場所に置く', 'minutes' => 10], ['name' => '明日の服を用意する', 'minutes' => 5], ['name' => '照明を落として本を読む', 'minutes' => 15]]],
            ['name' => '朝の軽い筋力トレーニング', 'memo' => '短時間でも体を動かして、一日を気持ちよく始めます。', 'tagNumbers' => [33, 3], 'actions' => [['name' => 'スクワットをする', 'minutes' => 10], ['name' => 'プランクをする', 'minutes' => 5], ['name' => '水分を補給する', 'minutes' => 5]]],
            ['name' => '英語ニュースを読む', 'memo' => '興味のある話題を英語で読み、気になった表現を残します。', 'tagNumbers' => [22, 6], 'actions' => [['name' => '英語ニュースを一つ読む', 'minutes' => 15], ['name' => '知らない表現を三つメモする', 'minutes' => 15], ['name' => '声に出して一文読む', 'minutes' => 5]]],
            ['name' => '週末のキッチンリセット', 'memo' => '次の食事を作りやすくするため、キッチンを整えます。', 'tagNumbers' => [13, 12], 'actions' => [['name' => '調理台を拭く', 'minutes' => 10], ['name' => '冷蔵庫の残り物を確認する', 'minutes' => 10], ['name' => '生ごみをまとめる', 'minutes' => 5]]],
            ['name' => '昼休みの散歩', 'memo' => '座り続けた体をほぐし、午後の集中を取り戻します。', 'tagNumbers' => [9, 39], 'actions' => [['name' => '外の空気を吸う', 'minutes' => 5], ['name' => '近所を歩く', 'minutes' => 15], ['name' => '帰って水を飲む', 'minutes' => 5]]],
            ['name' => '朝食を整える', 'memo' => '忙しい朝でも栄養を意識して、落ち着いて食事を取ります。', 'tagNumbers' => [11, 12], 'actions' => [['name' => '果物を一つ用意する', 'minutes' => 5], ['name' => 'たんぱく質を加える', 'minutes' => 10], ['name' => '食器を洗う', 'minutes' => 5]]],
            ['name' => '家計を振り返る', 'memo' => '今週の支出を確認し、次の買い物の目安を決めます。', 'tagNumbers' => [15, 37], 'actions' => [['name' => 'レシートを集める', 'minutes' => 5], ['name' => '支出を記録する', 'minutes' => 15], ['name' => '来週の予算を決める', 'minutes' => 10]]],
            ['name' => '肩こりリセットストレッチ', 'memo' => '画面作業の合間に肩と首をゆっくりほぐします。', 'tagNumbers' => [8, 40], 'actions' => [['name' => '肩を回す', 'minutes' => 5], ['name' => '首を横に伸ばす', 'minutes' => 5], ['name' => '深呼吸をする', 'minutes' => 3]]],
            ['name' => '読書の時間をつくる', 'memo' => '通知を離れて本に集中し、気づきを一つ残します。', 'tagNumbers' => [5, 2], 'actions' => [['name' => '通知を止める', 'minutes' => 2], ['name' => '本を読む', 'minutes' => 20], ['name' => '気づきをメモする', 'minutes' => 5]]],
            ['name' => '洗面台を整える', 'memo' => '一日の終わりに洗面台を片付け、朝を気持ちよく始めます。', 'tagNumbers' => [13, 14], 'actions' => [['name' => '洗面台を拭く', 'minutes' => 5], ['name' => 'タオルを替える', 'minutes' => 3], ['name' => '使った物を戻す', 'minutes' => 4]]],
        ];

        return $templates[($number - 5) % count($templates)];
    }

    /** @return array{name: string, memo: string, tagNumbers: list<int>, actions: list<array{name: string, minutes: int}>} */
    private function routineTemplateForRoutineNumber(int $number): array
    {
        return $this->routineTemplate($number + intdiv($number - 5, 250));
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
        ?string $routineExecutionIdentifier = null,
    ): array {
        return [
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
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
