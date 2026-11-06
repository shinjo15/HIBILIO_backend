<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use App\Models\RoutineActionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Application\UseCase\UpdateRoutineVideoLink\UpdateRoutineVideoLinkInputPort;
use Src\Routine\Application\UseCase\UpdateRoutineVideoLink\UpdateRoutineVideoLinkInterface;
use Src\Routine\Application\UseCase\UpdateRoutineVideoLink\UpdateRoutineVideoLinkOutput;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class RoutineActionVideoLinkTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_creation_stores_each_action_video_and_details_return_it(): void
    {
        $owner = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $owner, 'account_name' => 'owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession(['account_identifier' => $owner])->postJson('/api/routines', [
            'routine_name' => 'routine',
            'video_url' => 'https://example.com/routine',
            'routine_actions' => [
                ['routine_action_name' => 'first', 'video_url' => 'https://example.com/first'],
                ['routine_action_name' => 'second', 'video_url' => 'https://example.com/second'],
                ['routine_action_name' => 'legacy'],
            ],
        ])->assertCreated();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'second', 'video_url' => 'https://example.com/second']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'legacy', 'video_url' => null]);
        $routine = DB::table('routines')->where('routine_name', 'routine')->value('routine_identifier');
        $response = $this->getJson('/api/routines/'.$routine)->assertOk()->assertJsonPath('video_url', 'https://example.com/routine');
        $actions = collect($response->json('routine_actions'))->keyBy('action_name');
        self::assertSame('https://example.com/first', $actions['first']['video_url']);
        self::assertSame('https://example.com/second', $actions['second']['video_url']);
        self::assertNull($actions['legacy']['video_url']);
    }

    public function test_owner_can_update_keep_and_clear_only_the_target_action_video(): void
    {
        $this->insertFixture();
        $url = '/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link';
        $before = (array) DB::table('routine_actions')->where('action_name', 'first')->first();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->patchJson($url, ['video_url' => 'https://example.com/new'])->assertNoContent();
        $this->patchJson($url, [])->assertNoContent();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/new']);
        $this->patchJson($url, ['video_url' => null])->assertNoContent();
        $after = (array) DB::table('routine_actions')->where('action_name', 'first')->first();
        unset($before['video_url'], $before['updated_at'], $after['video_url'], $after['updated_at']);
        self::assertSame($before, $after);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => null]);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'second', 'video_url' => 'https://example.com/second']);
        $this->assertDatabaseHas('routines', ['routine_name' => 'routine', 'video_url' => 'https://example.com/routine']);
    }

    public function test_action_updates_reject_other_owner_wrong_routine_and_inactive_action(): void
    {
        $this->insertFixture();
        $url = '/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link';
        $this->patchJson($url, ['video_url' => null])->assertUnauthorized();
        $this->withSession(['account_identifier' => '22222222-2222-4222-8222-222222222222'])->patchJson($url, ['video_url' => null])->assertForbidden();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->patchJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee/video-link', ['video_url' => null])->assertNotFound();
        DB::table('routine_actions')->where('action_name', 'first')->update(['available' => false]);
        $this->patchJson($url, ['video_url' => null])->assertNotFound();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
    }

    public function test_action_cannot_update_another_owners_action_through_the_owners_routine(): void
    {
        $this->insertFixture();
        DB::table('routines')->where('routine_identifier', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->update(['account_identifier' => '22222222-2222-4222-8222-222222222222']);
        $routines = DB::table('routines')->orderBy('routine_identifier')->get()->toArray();
        $actions = DB::table('routine_actions')->orderBy('routine_action_identifier')->get()->toArray();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->patchJson('/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee/video-link', ['video_url' => 'https://example.com/attack'])->assertNotFound();
        self::assertEquals($routines, DB::table('routines')->orderBy('routine_identifier')->get()->toArray());
        self::assertEquals($actions, DB::table('routine_actions')->orderBy('routine_action_identifier')->get()->toArray());
    }

    public function test_action_uses_the_update_port_and_maps_route_account_and_url_presence(): void
    {
        $this->insertFixture();
        self::assertTrue(interface_exists(UpdateRoutineVideoLinkInterface::class));
        $inputs = [];
        $this->mock(UpdateRoutineVideoLinkInterface::class)->shouldReceive('execute')->times(3)
            ->withArgs(static function (UpdateRoutineVideoLinkInputPort $input) use (&$inputs): bool {
                $inputs[] = [$input->routineIdentifier()->value(), $input->accountIdentifier()->value(), $input->routineActionIdentifier()?->value(), $input->videoUrlProvided(), $input->videoUrl()?->value()];

                return true;
            })->andReturnUsing(static fn () => new UpdateRoutineVideoLinkOutput);
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111']);
        $routine = '/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->patchJson($routine.'/video-link', [])->assertNoContent();
        $this->patchJson($routine.'/video-link', ['video_url' => null])->assertNoContent();
        $this->patchJson($routine.'/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link', ['video_url' => 'https://example.com/action'])->assertNoContent();
        self::assertSame([
            ['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', null, false, null],
            ['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', null, true, null],
            ['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '11111111-1111-4111-8111-111111111111', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', true, 'https://example.com/action'],
        ], $inputs);
    }

    public function test_invalid_action_video_urls_are_rejected_on_create_and_update(): void
    {
        $this->insertFixture();
        $url = '/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link';
        foreach (['', ' ', ' https://example.com ', 0, [], 'javascript:alert(1)', 'https://example.com/'.str_repeat('a', 2030)] as $invalid) {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', ['routine_name' => 'invalid', 'routine_actions' => [['routine_action_name' => 'action', 'video_url' => $invalid]]])->assertUnprocessable()->assertJsonValidationErrors(['routine_actions.0.video_url']);
            $this->patchJson($url, ['video_url' => $invalid])->assertUnprocessable();
        }
        $this->assertDatabaseMissing('routines', ['routine_name' => 'invalid']);
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
    }

    public function test_non_array_actions_are_validation_errors(): void
    {
        $this->insertFixture();
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->postJson('/api/routines', ['routine_name' => 'invalid', 'routine_actions' => 'not an array'])->assertUnprocessable();
    }

    public function test_action_update_rolls_back_on_failure_and_rejects_inactive_owner(): void
    {
        $this->insertFixture();
        $url = '/api/routines/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa/actions/cccccccc-cccc-4ccc-8ccc-cccccccccccc/video-link';
        RoutineActionModel::updated(static function (): void {
            throw new \RuntimeException('test failure');
        });
        try {
            $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])->patchJson($url, ['video_url' => 'https://example.com/new'])->assertServerError();
            $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
        } finally {
            RoutineActionModel::flushEventListeners();
        }
        DB::table('accounts')->where('account_identifier', '11111111-1111-4111-8111-111111111111')->update(['status' => 'temporarily_banned']);
        $this->patchJson($url, ['video_url' => null])->assertUnauthorized();
        $this->assertDatabaseHas('routine_actions', ['action_name' => 'first', 'video_url' => 'https://example.com/first']);
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
                'video_url' => 'https://example.com/routine',
                'routine_actions' => [['routine_action_name' => 'rolled back action', 'video_url' => 'https://example.com/action']],
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
        DB::table('routine_actions')->insert(['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'action_name' => 'old action', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
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
        foreach ([['cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'first'], ['dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'second'], ['eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'other']] as [$id, $routine, $name]) {
            DB::table('routine_actions')->insert(['routine_action_identifier' => $id, 'routine_identifier' => $routine, 'action_name' => $name, 'video_url' => 'https://example.com/'.$name, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
