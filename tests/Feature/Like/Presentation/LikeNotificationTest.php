<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Src\Like\Application\UseCase\CreateLike\CreateLikeInput;
use Src\Like\Application\UseCase\CreateLike\CreateLikeInterface;
use Src\Like\Domain\Entity\LikeNotification;
use Src\Like\Domain\Repository\LikeNotificationRepositoryInterface;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Post\Domain\Exception\UnsupportedPostLikeException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Tests\TestCase;

final class LikeNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ACTOR = '11111111-1111-4111-8111-111111111111';

    private const AUTHOR = '22222222-2222-4222-8222-222222222222';

    private const ROUTINE = '33333333-3333-4333-8333-333333333333';

    private const POST = '44444444-4444-4444-8444-444444444444';

    public function test_like_notifies_author_and_existing_inbox_can_mark_it_read(): void
    {
        $this->fixtures();
        $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => self::POST, 'account_identifier' => self::AUTHOR])->assertNoContent();
        $response = $this->withSession(['account_identifier' => self::AUTHOR])->getJson('/api/my/notifications');
        $response->assertOk()->assertJsonPath('total', 1)->assertJsonPath('notifications.0.status', 'unread')->assertJsonPath('notifications.0.content', '花子さんが「朝の運動」にいいねしました。');
        $id = $response->json('notifications.0.notification_identifier');
        $this->withSession(['account_identifier' => self::ACTOR])->patchJson("/api/my/notifications/{$id}/read")->assertForbidden();
        $this->withSession(['account_identifier' => self::AUTHOR])->patchJson("/api/my/notifications/{$id}/read")->assertNoContent();
        $this->withSession(['account_identifier' => self::AUTHOR])->getJson('/api/my/notifications')->assertJsonPath('notifications.0.status', 'read');
        $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => self::POST])->assertStatus(409);
        $this->assertDatabaseCount('personal_notifications', 1);
        $this->assertDatabaseCount('likes', 1);
        $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_like_count' => 1]);
    }

    public function test_notifications_off_still_saves_like_without_notification(): void
    {
        $this->fixtures();
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['like_notifications_enabled' => false]);
        $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => self::POST])->assertNoContent();
        $this->assertDatabaseCount('likes', 1);
        $this->assertDatabaseCount('personal_notifications', 0);
        $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_like_count' => 1]);
    }

    public function test_self_like_creates_notification(): void
    {
        $this->fixtures();
        $this->withSession(['account_identifier' => self::AUTHOR])->postJson('/api/likes', ['post_identifier' => self::POST])->assertNoContent();
        $this->assertDatabaseHas('personal_notifications', ['receiver_account_identifier' => self::AUTHOR, 'content' => '太郎さんが「朝の運動」にいいねしました。', 'status' => 'unread']);
    }

    public function test_notification_failure_rolls_back_like_and_count_and_inserted_notification(): void
    {
        $this->fixtures();
        $real = $this->app->make(LikeNotificationRepositoryInterface::class);
        $this->app->instance(LikeNotificationRepositoryInterface::class, new class($real) implements LikeNotificationRepositoryInterface
        {
            public function __construct(private LikeNotificationRepositoryInterface $real) {}

            public function findTarget(AccountIdentifier $actor, RoutineIdentifier $routine): ?LikeNotificationTarget
            {
                return $this->real->findTarget($actor, $routine);
            }

            public function save(LikeNotification $notification): void
            {
                $this->real->save($notification);
                throw new RuntimeException('save failed');
            }
        });
        try {
            $this->app->make(CreateLikeInterface::class)->execute(new CreateLikeInput(new AccountIdentifier(self::ACTOR), new PostIdentifier(self::POST)));
            self::fail('Expected notification save failure');
        } catch (RuntimeException $exception) {
            self::assertSame('save failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('personal_notifications', 0);
        $this->assertDatabaseCount('likes', 0);
        $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_like_count' => 0]);
    }

    public function test_missing_deleted_and_banned_session_accounts_cannot_like(): void
    {
        $this->fixtures();
        foreach ([['available' => false, 'status' => 'active'], ['available' => true, 'status' => 'temporarily_banned']] as $state) {
            DB::table('accounts')->where('account_identifier', self::ACTOR)->update($state);
            $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => self::POST])->assertUnauthorized();
        }
        DB::table('accounts')->where('account_identifier', self::ACTOR)->delete();
        $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => self::POST])->assertUnauthorized();
        $this->assertDatabaseCount('personal_notifications', 0);
    }

    public function test_missing_post_and_wrong_post_category_never_notify(): void
    {
        $this->fixtures();
        $this->withSession(['account_identifier' => self::ACTOR])->postJson('/api/likes', ['post_identifier' => '55555555-5555-4555-8555-555555555555'])->assertNotFound();
        $execution = '66666666-6666-4666-8666-666666666666';
        DB::table('routine_executions')->insert(['routine_execution_identifier' => $execution, 'executor_account_identifier' => self::AUTHOR, 'routine_identifier' => self::ROUTINE, 'executed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->where('post_identifier', self::POST)->update(['post_category' => 'action', 'routine_execution_identifier' => $execution]);
        try {
            $this->app->make(CreateLikeInterface::class)->execute(new CreateLikeInput(new AccountIdentifier(self::ACTOR), new PostIdentifier(self::POST)));
            self::fail('Expected unsupported category');
        } catch (UnsupportedPostLikeException) {
            $this->assertDatabaseCount('likes', 0);
            $this->assertDatabaseCount('personal_notifications', 0);
        }
    }

    private function fixtures(): void
    {
        foreach ([self::ACTOR => '花子', self::AUTHOR => '太郎'] as $id => $name) {
            DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => $name, 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['like_notifications_enabled' => true]);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => '朝の運動', 'account_identifier' => self::AUTHOR, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => self::POST, 'routine_identifier' => self::ROUTINE, 'post_category' => 'routine', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
