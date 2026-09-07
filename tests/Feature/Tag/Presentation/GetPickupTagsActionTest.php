<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetPickupTagsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_pickup_tags_without_authentication_in_tag_name_and_identifier_ascending_order(): void
    {
        $this->insertTag('ffffffff-ffff-4fff-8fff-ffffffffffff', '読書', true, true);
        $this->insertTag('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '読書', false, true);
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '朝活', true, true);
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '運動', true, false);
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '料理', true);

        $this->getJson('/api/tags/pickup')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    [
                        'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                        'tag_name' => '朝活',
                    ],
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

    private function insertTag(string $identifier, string $name, bool $available, ?bool $pickup = null): void
    {
        $tag = [
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => $available,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($pickup !== null) {
            $tag['pickup'] = $pickup;
        }

        DB::table('tags')->insert($tag);
    }
}
