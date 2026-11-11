<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use App\Models\RoutineActionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class RoutineActionVideoLinkTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_customization_validates_both_video_fields_and_preserves_original_urls(): void
    {
        $this->insertFixture();
        $owner = '11111111-1111-4111-8111-111111111111';
        $valid = ['https://www.youtube.com/watch?list=abc&v=dQw4w9WgXcQ&t=5', 'https://youtu.be/dQw4w9WgXcQ?si=xyz', 'https://m.youtube.com/shorts/dQw4w9WgXcQ', 'https://youtube.com/live/dQw4w9WgXcQ?feature=share', 'https://www.youtube.com/embed/dQw4w9WgXcQ'];
        foreach ($valid as $url) {
            $this->withSession(['account_identifier' => $owner])->postJson('/api/routines', [
                'routine_name' => 'custom', 'parent_routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'video_url' => $url, 'routine_actions' => [['routine_action_name' => 'custom action', 'video_url' => $url]],
            ])->assertCreated();
            $this->assertDatabaseHas('routines', ['routine_name' => 'custom', 'video_url' => $url]);
            $this->assertDatabaseHas('routine_actions', ['action_name' => 'custom action', 'video_url' => $url]);
        }
    }

    public function test_invalid_urls_in_either_field_never_persist_a_customization(): void
    {
        $this->insertFixture();
        $invalid = [
            'https://example.com/video', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
            'https://youtube.com@evil.test/watch?v=dQw4w9WgXcQ', 'https://user@youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com:443/watch?v=dQw4w9WgXcQ', 'https://youtube.com/watch',
            'https://youtube.com/watch?v=short', 'https://youtube.com/watch?v=dQw4w9WgXcQ&v=bad',
            'https://youtube.com/watch?v=dQw4w9WgXcQ&v%5B%5D=bad', 'https://youtu.be/',
            'https://youtube.com/shorts/dQw4w9WgXcQ/extra', 'ftp://youtu.be/dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ%zz', 'https://youtube.com/channel/dQw4w9WgXcQ',
            'https://youtube.com/live/short', 'https://youtube.com/embed/dQw4w9WgXcQ/extra',
            'https://youtube.com.evil.test/live/dQw4w9WgXcQ', 'https://youtu.be/embed/dQw4w9WgXcQ',
        ];
        foreach ($invalid as $url) {
            foreach (['video_url', 'routine_actions.0.video_url'] as $field) {
                $payload = [
                    'routine_name' => 'invalid custom', 'parent_routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                    'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
                    'routine_actions' => [['routine_action_name' => 'invalid custom action', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']],
                ];
                if ($field === 'video_url') {
                    $payload['video_url'] = $url;
                } else {
                    $payload['routine_actions'][0]['video_url'] = $url;
                }
                $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
                    ->postJson('/api/routines', $payload)->assertUnprocessable()->assertJsonValidationErrors([$field]);
            }
        }
        $this->assertDatabaseCount('routines', 2);
        $this->assertDatabaseCount('routine_actions', 3);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_legacy_urls_remain_readable_in_details_and_action_restoration(): void
    {
        $this->insertFixture();
        $this->getJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->assertOk()
            ->assertJsonPath('video_url', 'https://example.com/routine')
            ->assertJsonPath('routine_actions.0.video_url', 'https://example.com/first');
        $actions = $this->app->make(RoutineActionRepositoryInterface::class)->findByIdentifiers([
            new RoutineActionIdentifier('cccccccc-cccc-4ccc-8ccc-cccccccccccc'),
        ]);
        self::assertSame('https://example.com/first', $actions[0]->videoUrl()?->value());
    }

    public function test_creation_stores_each_action_video_and_details_return_it(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession(['account_identifier' => $owner])->postJson('/api/routines', [
            'routine_name' => 'routine',
            'video_url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ',
            'routine_actions' => [
                ['routine_action_name' => 'first', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'],
                ['routine_action_name' => 'second', 'video_url' => 'https://youtube.com/shorts/dQw4w9WgXcQ'],
                ['routine_action_name' => 'legacy'],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'second', 'video_url' => 'https://youtube.com/shorts/dQw4w9WgXcQ']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'legacy', 'video_url' => null]);
        $routine = DB::table('routines')->where('routine_name', 'routine')->value('routine_identifier');
        $response = $this->getJson('/api/routines/'.$routine)->assertOk()->assertJsonPath('video_url', 'https://youtube.com/watch?v=dQw4w9WgXcQ');
        $actions = collect($response->json('routine_actions'))->keyBy('action_name');
        self::assertSame('https://youtu.be/dQw4w9WgXcQ', $actions['first']['video_url']);
        self::assertSame('https://youtube.com/shorts/dQw4w9WgXcQ', $actions['second']['video_url']);
        self::assertNull($actions['legacy']['video_url']);
    }

    public function test_creation_preserves_urls_for_mixed_compound_and_plain_action_keys(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
            'routine_name' => 'compound keys',
            'routine_actions' => [
                'x.*' => ['routine_action_name' => 'compound', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=compound'],
                'plain' => ['routine_action_name' => 'normal', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=normal', 'parent_routine_action_index' => 0],
                'x.y' => ['routine_action_name' => 'dotted', 'video_url' => null],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'compound', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=compound']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'normal', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=normal']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'dotted', 'video_url' => null]);
        $parent = DB::table('routine_actions')->where('action_name', 'compound')->value('routine_action_identifier');
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'normal', 'parent_routine_action_identifier' => $parent]);
    }

    public function test_creation_rejects_invalid_url_for_compound_key_among_plain_actions(): void
    {
        $this->insertFixture();
        foreach (['javascript:alert(1)', 'http://example.com/invalid', '', ' ', ' https://example.com ', [], 0] as $invalid) {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
                'routine_name' => 'invalid compound',
                'routine_actions' => [
                    'x.*' => ['routine_action_name' => 'compound', 'video_url' => $invalid],
                    'plain' => ['routine_action_name' => 'normal', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'],
                ],
            ])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.x.*.video_url']);
        }
        $this->assertDatabaseMissing('routines', ['routine_name' => 'invalid compound']);
        $this->assertDatabaseMissing('routine_actions', ['action_name' => 'compound']);
        $this->assertDatabaseMissing('routine_actions', ['action_name' => 'normal']);
    }

    public function test_associative_actions_preserve_omitted_null_and_plain_key_validation_errors(): void
    {
        $this->insertFixture();
        $owner = '11111111-1111-4111-8111-111111111111';
        $this->withSession(['account_identifier' => $owner])->postJson('/api/routines', [
            'routine_name' => 'optional videos',
            'routine_actions' => [
                'x.*' => ['routine_action_name' => 'omitted'],
                'plain' => ['routine_action_name' => 'explicit null', 'video_url' => null],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'omitted', 'video_url' => null]);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'explicit null', 'video_url' => null]);
        $this->postJson('/api/routines', [
            'routine_name' => 'invalid plain',
            'routine_actions' => [
                'x.*' => ['routine_action_name' => 'valid', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'],
                'plain' => ['routine_action_name' => 'invalid', 'video_url' => 'javascript:alert(1)'],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.plain.video_url']);
        $this->assertDatabaseMissing('routines', ['routine_name' => 'invalid plain']);
    }

    public function test_creation_preserves_video_urls_for_associative_action_keys_with_dots(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
            'routine_name' => 'dotted keys',
            'routine_actions' => [
                'x.y' => ['routine_action_name' => 'dotted', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=dotted'],
                'plain' => ['routine_action_name' => 'plain', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=plain'],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'dotted', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=dotted']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'plain', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ?x=plain']);
    }

    public function test_creation_rejects_invalid_video_url_for_associative_action_key_with_dot(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
            'routine_name' => 'invalid dotted key',
            'routine_actions' => ['x.y' => ['routine_action_name' => 'dotted', 'video_url' => 'javascript:alert(1)']],
        ])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.x.y.video_url']);
        $this->assertDatabaseMissing('routines', ['routine_name' => 'invalid dotted key']);
        $this->assertDatabaseMissing('routine_actions', ['action_name' => 'dotted']);
    }

    public function test_creation_preserves_video_url_for_literal_asterisk_action_key(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
            'routine_name' => 'asterisk valid',
            'routine_actions' => ['*' => ['routine_action_name' => 'asterisk', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'asterisk', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    }

    public function test_creation_rejects_invalid_video_url_for_literal_asterisk_action_key(): void
    {
        $this->insertFixture();
        foreach (['', 'http://example.com/invalid'] as $invalid) {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
                'routine_name' => 'asterisk key',
                'routine_actions' => [
                    '*' => ['routine_action_name' => 'asterisk', 'video_url' => $invalid],
                    'plain' => ['routine_action_name' => 'plain', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'],
                ],
            ])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.*.video_url']);
        }
        $this->assertDatabaseMissing('routines', ['routine_name' => 'asterisk key']);
        $this->assertDatabaseMissing('routine_actions', ['action_name' => 'asterisk']);
        $this->assertDatabaseMissing('routine_actions', ['action_name' => 'plain']);
    }

    public function test_video_link_update_routes_are_not_available(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111']);
        $this->patchJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/video-link', ['video_url' => null])->assertNotFound();
        $this->patchJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link', ['video_url' => null])->assertNotFound();
    }

    public function test_invalid_action_video_urls_are_rejected_on_create(): void
    {
        $this->insertFixture();
        foreach (['', ' ', ' https://example.com ', 0, [], 'javascript:alert(1)', 'https://example.com/'.str_repeat('a', 2030)] as $invalid) {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', ['routine_name' => 'invalid', 'routine_actions' => [['routine_action_name' => 'action', 'video_url' => $invalid]]])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.0.video_url']);
        }
        $this->assertDatabaseMissing('routines', ['routine_name' => 'invalid']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
    }

    public function test_non_array_actions_are_validation_errors(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', ['routine_name' => 'invalid', 'routine_actions' => 'not an array'])->assertUnprocessable();
    }

    public function test_action_video_is_not_inherited_from_parent_routine(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
            'routine_name' => 'customized',
            'parent_routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'routine_actions' => [['routine_action_name' => 'customized action']],
        ])->assertCreated();
        $this->assertDatabaseHas('routines', ['routine_name' => 'customized', 'video_url' => null]);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'customized action', 'video_url' => null]);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
    }

    public function test_creation_rolls_back_routine_and_actions_if_an_action_save_fails(): void
    {
        $this->insertFixture();
        RoutineActionModel::created(static function (): void {
            throw new \LogicException('test failure');
        });
        try {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', [
                'routine_name' => 'rolled back',
                'video_url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ',
                'routine_actions' => [['routine_action_name' => 'rolled back action', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']],
            ])->assertServerError();
            $this->assertDatabaseMissing('routines', ['routine_name' => 'rolled back']);
            $this->assertDatabaseMissing('routine_actions', ['action_name' => 'rolled back action']);
        } finally {
            RoutineActionModel::flushEventListeners();
        }
    }

    public function test_migration_preserves_existing_action_data_and_defaults_video_to_null(): void
    {
        $migration = require database_path('migrations/2026_10_30_000001_add_video_url_to_routine_actions_table.php');
        $migration->down();
        DB::table('accounts')->insert(['account_identifier' => '11111111-1111-4111-8111-111111111111', 'account_name' => 'owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => '11111111-1111-4111-8111-111111111111', 'routine_name' => 'old', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'action_name' => 'old action', 'position' => 0, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'old action', 'video_url' => null, 'available' => true]);
    }

    private function insertFixture(): void
    {
        foreach (['11111111-1111-4111-8111-111111111111', '22222222-2222-4222-8222-222222222222'] as $owner) {
            DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => $owner, 'email_address' => $owner.'@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('routines')->insert(['routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'account_identifier' => '11111111-1111-4111-8111-111111111111', 'routine_name' => 'routine', 'video_url' => 'https://example.com/routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'account_identifier' => '11111111-1111-4111-8111-111111111111', 'routine_name' => 'other routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'first', 0], ['dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'second', 1], ['eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'other', 0]] as [$id, $routine, $name, $position]) {
            DB::table('routine_actions')->insert(['routine_action_identifier' => $id, 'routine_identifier' => $routine, 'action_name' => $name, 'position' => $position, 'video_url' => 'https://example.com/'.$name, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
