<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Application\UseCase\CreateSupport;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;
use Src\Support\Application\UseCase\CreateSupport\CreateSupportInput;
use Src\Support\Application\UseCase\CreateSupport\CreateSupportInterface;
use Src\Support\Domain\Entity\SupportNotification;
use Src\Support\Domain\Exception\AlreadySupportedException;
use Src\Support\Domain\Repository\SupportNotificationRepositoryInterface;
use Src\Support\Domain\ValueObject\SupportNotificationTarget;
use Tests\TestCase;

final class SupportNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ACTOR = '11111111-1111-4111-8111-111111111111';

    private const EXECUTOR = '22222222-2222-4222-8222-222222222222';

    private const AUTHOR = '33333333-3333-4333-8333-333333333333';

    private const ROUTINE = '44444444-4444-4444-8444-444444444444';

    private const EXECUTION = '55555555-5555-4555-8555-555555555555';

    private const POST = '66666666-6666-4666-8666-666666666666';

    public function test_on_notifies_executor_not_routine_author_and_duplicate_does_not_notify_again(): void
    {
        $this->fixtures(true);
        $this->execute(self::ACTOR);
        $this->assertDatabaseHas('personal_notifications', ['receiver_account_identifier' => self::EXECUTOR, 'content' => '花子さんが「朝の運動」の実行を応援しました。', 'status' => 'unread']);
        $this->assertDatabaseCount('personal_notifications', 1);
        try {
            $this->execute(self::ACTOR);
            self::fail('Expected duplicate support');
        } catch (AlreadySupportedException) {
            $this->assertDatabaseCount('personal_notifications', 1);
            $this->assertDatabaseCount('supports', 1);
            $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_support_count' => 1]);
        }
    }

    public function test_off_saves_support_without_notification_even_when_author_is_enabled(): void
    {
        $this->fixtures(false);
        $this->execute(self::ACTOR);
        $this->assertDatabaseCount('personal_notifications', 0);
        $this->assertDatabaseCount('supports', 1);
        $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_support_count' => 1]);
    }

    public function test_self_support_notifies_executor(): void
    {
        $this->fixtures(true);
        $this->execute(self::EXECUTOR);
        $this->assertDatabaseHas('personal_notifications', ['receiver_account_identifier' => self::EXECUTOR, 'content' => '太郎さんが「朝の運動」の実行を応援しました。', 'status' => 'unread']);
    }

    public function test_notification_failure_rolls_back_all_changes(): void
    {
        $this->fixtures(true);
        $real = $this->app->make(SupportNotificationRepositoryInterface::class);
        $this->app->instance(SupportNotificationRepositoryInterface::class, new class($real) implements SupportNotificationRepositoryInterface
        {
            public function __construct(private SupportNotificationRepositoryInterface $real) {}

            public function findTarget(AccountIdentifier $actor, PostIdentifier $post): ?SupportNotificationTarget
            {
                return $this->real->findTarget($actor, $post);
            }

            public function save(SupportNotification $notification): void
            {
                $this->real->save($notification);
                throw new RuntimeException('save failed');
            }
        });
        try {
            $this->execute(self::ACTOR);
            self::fail('Expected notification failure');
        } catch (RuntimeException $exception) {
            self::assertSame('save failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('supports', 0);
        $this->assertDatabaseCount('personal_notifications', 0);
        $this->assertDatabaseHas('posts', ['post_identifier' => self::POST, 'post_support_count' => 0]);
    }

    public function test_migration_defaults_to_false_for_existing_and_new_accounts(): void
    {
        $migration = require database_path('migrations/2026_11_09_000001_add_support_notifications_enabled_to_accounts_table.php');
        $migration->down();
        $this->insertAccount(self::ACTOR, '花子');
        try {
            $migration->up();
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::ACTOR, 'support_notifications_enabled' => false]);
            $this->insertAccount(self::EXECUTOR, '太郎');
            $this->assertDatabaseHas('accounts', ['account_identifier' => self::EXECUTOR, 'support_notifications_enabled' => false]);
        } finally {
            if (! Schema::hasColumn('accounts', 'support_notifications_enabled')) {
                $migration->up();
            }
        }
    }

    private function execute(string $actor): void
    {
        $this->app->make(CreateSupportInterface::class)->execute(new CreateSupportInput(new AccountIdentifier($actor), new PostIdentifier(self::POST)));
    }

    private function fixtures(bool $enabled): void
    {
        $this->insertAccount(self::ACTOR, '花子');
        $this->insertAccount(self::EXECUTOR, '太郎');
        $this->insertAccount(self::AUTHOR, '作者');
        DB::table('accounts')->where('account_identifier', self::EXECUTOR)->update(['support_notifications_enabled' => $enabled]);
        DB::table('accounts')->where('account_identifier', self::AUTHOR)->update(['support_notifications_enabled' => true]);
        DB::table('routines')->insert(['routine_identifier' => self::ROUTINE, 'routine_name' => '朝の運動', 'account_identifier' => self::AUTHOR, 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('routine_executions')->insert(['routine_execution_identifier' => self::EXECUTION, 'executor_account_identifier' => self::EXECUTOR, 'routine_identifier' => self::ROUTINE, 'executed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('posts')->insert(['post_identifier' => self::POST, 'routine_identifier' => self::ROUTINE, 'routine_execution_identifier' => self::EXECUTION, 'post_category' => 'action', 'available' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function insertAccount(string $id, string $name): void
    {
        DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => $name, 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }
}
