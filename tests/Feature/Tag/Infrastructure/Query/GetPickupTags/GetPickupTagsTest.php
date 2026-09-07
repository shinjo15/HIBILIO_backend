<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Infrastructure\Query\GetPickupTags;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Tag\Application\Usecase\Query\GetPickupTags\GetPickupTagsInterface;
use Src\Tag\Infrastructure\Query\GetPickupTags\GetPickupTags;
use Tests\TestCase;

final class GetPickupTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_pickup_tags_in_tag_name_and_identifier_ascending_order_without_an_available_condition(): void
    {
        $this->insertTag('ffffffff-ffff-4fff-8fff-ffffffffffff', '読書', true, true);
        $this->insertTag('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '読書', false, true);
        $this->insertTag('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '朝活', true, true);
        $this->insertTag('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '運動', true, false);
        $this->insertTag('cccccccc-cccc-4ccc-8ccc-cccccccccccc', '料理', true);

        $result = (new GetPickupTags)->execute();

        self::assertSame([
            [
                'tagIdentifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'tagName' => '朝活',
            ],
            [
                'tagIdentifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
                'tagName' => '読書',
            ],
            [
                'tagIdentifier' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                'tagName' => '読書',
            ],
        ], $result->tags());
    }

    public function test_query_interface_is_bound_to_the_infrastructure_query(): void
    {
        self::assertInstanceOf(GetPickupTags::class, $this->app->make(GetPickupTagsInterface::class));
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
