<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetTagsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_available_tags_without_authentication(): void
    {
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '運動', true);
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '朝活', true);
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '非公開', false);

        $this->getJson('/api/tags')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                        'tag_name' => '朝活',
                    ],
                    [
                        'tag_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                        'tag_name' => '運動',
                    ],
                ],
            ]);
    }

    public function test_returns_only_available_tags_that_start_with_the_specified_tag_name(): void
    {
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '朝活', true);
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '朝食', true);
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '昼朝', true);
        $this->insertTag('dddddddd-dddd-4ddd-8ddd-dddddddddddd', '朝寝坊', false);

        $this->getJson('/api/tags?tag_name=朝')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                        'tag_name' => '朝活',
                    ],
                    [
                        'tag_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                        'tag_name' => '朝食',
                    ],
                ],
            ]);
    }

    public function test_treats_like_special_characters_in_the_specified_tag_name_as_literals(): void
    {
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '100%達成', true);
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '1000達成', true);
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'todo_完了', true);
        $this->insertTag('dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'todoX完了', true);

        $this->getJson('/api/tags?tag_name=100%25')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                        'tag_name' => '100%達成',
                    ],
                ],
            ]);

        $this->getJson('/api/tags?tag_name=todo_')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                        'tag_name' => 'todo_完了',
                    ],
                ],
            ]);
    }

    public function test_orders_tags_with_the_same_name_by_tag_identifier(): void
    {
        $this->insertTag('ffffffff-ffff-4fff-8fff-ffffffffffff', '読書', true);
        $this->insertTag('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '読書', true);

        $this->getJson('/api/tags')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
                        'tag_name' => '読書',
                    ],
                    [
                        'tag_identifier' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                        'tag_name' => '読書',
                    ],
                ],
            ]);
    }

    private function insertTag(string $identifier, string $name, bool $available): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
