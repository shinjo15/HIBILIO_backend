<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TagSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            '朝活', '集中', '運動', 'リラックス', '読書', '勉強', '瞑想', 'ストレッチ',
            '散歩', '睡眠', '食事', '料理', '掃除', '片付け', '家計管理', '日記',
            '写真', '音楽', '映画鑑賞', '植物の手入れ', '手帳', '語学', 'プログラミング',
            '創作', '絵を描く', '編み物', '旅行計画', '身だしなみ', 'スキンケア', '入浴',
            '水分補給', 'ランニング', '筋トレ', 'ヨガ', '自転車', '早起き', '振り返り',
            '目標設定', '休憩', '深呼吸',
        ];
        $timestamp = now();

        DB::transaction(function () use ($names, $timestamp): void {
            foreach ($names as $index => $name) {
                $identifier = sprintf('20000000-0000-4000-8000-%012d', $index + 1);
                if (DB::table('tags')->where('tag_identifier', $identifier)->exists()) {
                    continue;
                }

                if (DB::table('tags')->where('tag_name', $name)->exists()) {
                    throw new RuntimeException('Initial tag name conflicts with an existing tag: '.$identifier);
                }

                DB::table('tags')->insert([
                    'tag_identifier' => $identifier,
                    'tag_name' => $name,
                    'available' => true,
                    'pickup' => $index < 15,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        });
    }
}
