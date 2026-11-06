<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Presentation;

use App\Models\RoutineModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class RoutineVideoLinkActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    private const OWNER = '11111111-1111-4111-8111-111111111111';

    private const OTHER = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    public function test_owner_can_update_keep_and_clear_video_url_without_changing_other_attributes(): void
    {
        $this->insertRoutine();
        DB::table('tags')->insert(['tag_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'tag_name' => 'tag', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_tags')->insert(['routine_identifier' => self::ROUTINE, 'tag_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tags')->insert(['tag_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'tag_name' => 'inactive tag', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_tags')->insert(['routine_identifier' => self::ROUTINE, 'tag_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'available' => false, 'created_at' => now(), 'updated_at' => now()]);
        $before = (array) DB::table('routines')->where('routine_identifier', self::ROUTINE)->first();
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.org/watch?v=1'])->assertNoContent();
        $this->getJson('/api/routines/'.self::ROUTINE)->assertOk()->assertJsonPath('video_url', 'https://example.org/watch?v=1');
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', [])->assertNoContent();
        $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => 'https://example.org/watch?v=1']);
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => null])->assertNoContent();
        $after = (array) DB::table('routines')->where('routine_identifier', self::ROUTINE)->first();
        unset($before['video_url'], $before['updated_at'], $after['video_url'], $after['updated_at']);
        self::assertSame($before, $after);
        $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => null]);
        $this->assertDatabaseHas('routine_tags', ['routine_identifier' => self::ROUTINE, 'tag_identifier' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'available' => true]);
        $this->assertDatabaseHas('routine_tags', ['routine_identifier' => self::ROUTINE, 'tag_identifier' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'available' => false]);
        $this->assertDatabaseHas('routine_actions', ['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'action_name' => 'action']);
    }

    public function test_anonymous_other_owner_and_unavailable_or_missing_routines_cannot_be_updated(): void
    {
        $this->insertRoutine();
        $url = '/api/routines/'.self::ROUTINE.'/video-link';
        $this->patchJson($url, ['video_url' => 'https://example.com'])->assertUnauthorized();
        DB::table('accounts')->insert(['account_identifier' => self::OTHER, 'account_name' => 'other', 'email_address' => 'other@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->withSession(['account_identifier' => self::OTHER])->patchJson($url, ['video_url' => 'https://example.com'])->assertForbidden();
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb/video-link', ['video_url' => null])->assertNotFound();
        DB::table('routines')->where('routine_identifier', self::ROUTINE)->update(['available' => false]);
        $this->withSession(['account_identifier' => self::OWNER])->patchJson($url, ['video_url' => null])->assertNotFound();
        $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => null]);
    }

    public function test_invalid_values_are_rejected_without_changing_the_link(): void
    {
        $this->insertRoutine();
        DB::table('routines')->where('routine_identifier', self::ROUTINE)->update(['video_url' => 'https://example.com/original']);
        foreach (['', ' ', ' https://example.com ', '0', 0, [], 'javascript:alert(1)', 'https://127.0.0.999/', 'https://999.999.999.999/', 'https://1.2.3.08/', 'https://999999999999/', 'https://1.16777216/', 'https://example.com/" onclick="alert(1)', 'https://example.com/<script>', 'https://example.com/%zz', 'ftp://example.com', 'https://example.com/'.str_repeat('a', 2030)] as $invalid) {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => $invalid])->assertUnprocessable();
            $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => 'https://example.com/original']);
        }
    }

    public function test_accepts_2048_character_url(): void
    {
        $this->insertRoutine();
        $url = 'http://example.com/'.str_repeat('a', 2029);
        self::assertSame(2048, strlen($url));
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => $url])->assertNoContent();
        $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => $url]);
    }

    public function test_accepts_urls_on_arbitrary_hosts(): void
    {
        $this->insertRoutine();
        foreach (['http://localhost/video', 'http://127.0.0.1:8080/video', 'https://127.1/video', 'https://0x7f.1/video', 'https://0177.0.0.1/video', 'https://255.255.255.255/video', 'https://[::1]/video', 'https://example.org/video'] as $url) {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => $url])->assertNoContent();
            $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => $url]);
        }
    }

    public function test_accepts_and_preserves_internationalized_urls(): void
    {
        $this->insertRoutine();
        foreach (['https://例え.テスト/動画?q=日本語', 'https://example.com/日本語'] as $url) {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => $url])->assertNoContent();
            $this->getJson('/api/routines/'.self::ROUTINE)->assertOk()->assertJsonPath('video_url', $url);
        }
    }

    public function test_legacy_routine_with_no_active_actions_can_update_without_modifying_actions(): void
    {
        $this->insertRoutine();
        DB::table('routine_actions')->where('routine_identifier', self::ROUTINE)->update(['available' => false]);
        $routine = $this->app->make(RoutineRepositoryInterface::class)->find(new RoutineIdentifier(self::ROUTINE));
        self::assertSame([], $routine->routineActionIdentifiers()->values());
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.com/one'])->assertNoContent();
        $this->assertDatabaseHas('routine_actions', ['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'available' => false]);
        DB::table('routine_actions')->where('routine_identifier', self::ROUTINE)->delete();
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.com/two'])->assertNoContent();
        $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => 'https://example.com/two']);
        $this->assertDatabaseCount('routine_actions', 0);
    }

    public function test_unavailable_or_inactive_owner_cannot_update(): void
    {
        $this->insertRoutine();
        foreach ([['available' => false], ['available' => true, 'status' => 'temporarily_banned']] as $state) {
            DB::table('accounts')->where('account_identifier', self::OWNER)->update($state);
            $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.com'])->assertUnauthorized();
            $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => null]);
        }
    }

    public function test_unrelated_domain_failure_is_not_reported_as_owner_denial(): void
    {
        $this->insertRoutine();
        $this->mock(RoutineRepositoryInterface::class)->shouldReceive('find')->once()->andThrow(new \DomainException('unexpected failure'));
        $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.com'])->assertServerError();
    }

    public function test_a_failure_after_update_rolls_back_the_change(): void
    {
        $this->insertRoutine();
        RoutineModel::updated(static function (): void {
            throw new \RuntimeException('test failure');
        });
        try {
            $this->withSession(['account_identifier' => self::OWNER])->patchJson('/api/routines/'.self::ROUTINE.'/video-link', ['video_url' => 'https://example.com/new'])->assertServerError();
            $this->assertDatabaseHas('routines', ['routine_identifier' => self::ROUTINE, 'video_url' => null]);
        } finally {
            RoutineModel::flushEventListeners();
        }
    }

    private function insertRoutine(): void
    {
        DB::table('accounts')->insert(['account_identifier' => self::OWNER, 'account_name' => 'owner', 'email_address' => 'owner@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'account_identifier' => self::OWNER, 'routine_name' => 'routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'routine_identifier' => self::ROUTINE, 'action_name' => 'action', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
