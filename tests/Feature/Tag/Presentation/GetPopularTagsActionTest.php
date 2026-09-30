<?php

declare(strict_types=1);

namespace Tests\Feature\Tag\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetPopularTagsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_ranked_tags_without_authentication_including_zero_count_tags(): void
    {
        $this->insertAccount('10000000-0000-4000-8000-000000000001');
        $this->insertAccount('10000000-0000-4000-8000-000000000002', false);
        $this->insertAccount('10000000-0000-4000-8000-000000000003', true, 'private');
        $this->insertTag('20000000-0000-4000-8000-000000000001', 'alpha');
        $this->insertTag('20000000-0000-4000-8000-000000000002', 'beta');
        $this->insertTag('20000000-0000-4000-8000-000000000003', 'omega');
        $this->insertTag('20000000-0000-4000-8000-000000000004', 'unavailable-owner');
        $this->insertTag('20000000-0000-4000-8000-000000000005', 'private');
        $this->insertRoutine('30000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000001');
        $this->insertRoutine('30000000-0000-4000-8000-000000000004', '10000000-0000-4000-8000-000000000002');
        $this->insertRoutine('30000000-0000-4000-8000-000000000005', '10000000-0000-4000-8000-000000000003');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000001');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000002');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000004');
        $this->insertRoutineTag('30000000-0000-4000-8000-000000000005', '20000000-0000-4000-8000-000000000005');

        $this->getJson('/api/tags/popular')
            ->assertOk()
            ->assertExactJson([
                'tags' => [
                    ['tag_identifier' => '20000000-0000-4000-8000-000000000001', 'tag_name' => 'alpha', 'routine_count' => 2],
                    ['tag_identifier' => '20000000-0000-4000-8000-000000000002', 'tag_name' => 'beta', 'routine_count' => 1],
                    ['tag_identifier' => '20000000-0000-4000-8000-000000000003', 'tag_name' => 'omega', 'routine_count' => 0],
                    ['tag_identifier' => '20000000-0000-4000-8000-000000000005', 'tag_name' => 'private', 'routine_count' => 0],
                    ['tag_identifier' => '20000000-0000-4000-8000-000000000004', 'tag_name' => 'unavailable-owner', 'routine_count' => 0],
                ],
            ]);
    }

    private function insertAccount(string $identifier, bool $available = true, string $visibility = 'public'): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $identifier,
            'email_address' => "{$identifier}@example.com",
            'available' => $available,
            'status' => 'active',
            'visibility' => $visibility,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertTag(string $identifier, string $name): void
    {
        DB::table('tags')->insert([
            'tag_identifier' => $identifier,
            'tag_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutine(string $identifier, string $accountIdentifier): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'account_identifier' => $accountIdentifier,
            'routine_name' => $identifier,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRoutineTag(string $routineIdentifier, string $tagIdentifier): void
    {
        DB::table('routine_tags')->insert([
            'routine_identifier' => $routineIdentifier,
            'tag_identifier' => $tagIdentifier,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
