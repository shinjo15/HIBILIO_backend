<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Infrastructure\Query\GetPickupTags;

use App\Models\TagModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class GetPickupTagsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_defaults_to_false_and_is_cast_to_boolean(): void
    {
        self::assertTrue(Schema::hasColumn('tags', 'pickup'));

        $tag = TagModel::query()->create([
            'tag_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'tag_name' => '朝活',
            'available' => true,
        ]);

        self::assertFalse($tag->fresh()->pickup);
    }
}
