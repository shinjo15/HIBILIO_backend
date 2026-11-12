<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Infrastructure\Repository;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Account\Domain\Entity\Account as ProfileAccount;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Infrastructure\Repository\AccountRepository as ProfileRepository;
use Src\RoutineExecution\Domain\Repository\AccountRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class AccountRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_restores_and_saves_only_experience_and_missing_account_is_not_created(): void
    {
        $id = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $profile = new ProfileRepository;
        $profile->save(ProfileAccount::create($id, new AccountName('User'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([])));
        $this->assertDatabaseHas('accounts', ['account_identifier' => $id->value(), 'experience' => 0]);
        $stale = $profile->find($id);
        $repository = $this->app->make(AccountRepositoryInterface::class);
        DB::table('accounts')->where('account_identifier', $id->value())->update(['updated_at' => '2020-01-01 00:00:00']);
        DB::transaction(function () use ($repository, $id): void {
            $account = $repository->findWithLock($id);
            self::assertNotNull($account);
            $account->recordRoutineExecution();
            DB::table('accounts')->where('account_identifier', $id->value())->update(['account_name' => 'Updated']);
            $repository->save($account);
        });
        $this->assertDatabaseHas('accounts', ['account_identifier' => $id->value(), 'experience' => 100, 'account_name' => 'Updated', 'updated_at' => '2020-01-01 00:00:00']);
        $stale->changeUiMode(AccountUiMode::DARK);
        $profile->save($stale);
        $this->assertDatabaseHas('accounts', ['account_identifier' => $id->value(), 'experience' => 100]);
        self::assertNull($repository->findWithLock(new AccountIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7')));
        $this->assertDatabaseCount('accounts', 1);
    }

    public function test_migration_assigns_zero_to_existing_accounts(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->dropColumn('experience');
        });
        DB::table('accounts')->insert(['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'account_name' => 'User', 'email_address' => 'user@example.com']);
        $migration = require database_path('migrations/2026_11_10_000000_add_experience_to_accounts_table.php');
        $migration->up();
        $this->assertDatabaseHas('accounts', ['account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87', 'experience' => 0]);
    }
}
