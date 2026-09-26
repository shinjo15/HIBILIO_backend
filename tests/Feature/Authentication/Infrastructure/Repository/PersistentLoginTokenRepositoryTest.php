<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class PersistentLoginTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_and_restores_a_hashed_token_with_its_expiration(): void
    {
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $this->insertAccount($accountIdentifier);
        $plainTextValidator = 'plain-text-validator';
        $validatorHash = Hash::make($plainTextValidator);
        $token = new PersistentLoginToken(
            new PersistentLoginSelector('persistent-login-selector'),
            $accountIdentifier,
            new PersistentLoginValidatorHash($validatorHash),
            new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00')),
        );
        $repository = $this->app->make(PersistentLoginTokenRepositoryInterface::class);

        $repository->save($token);
        $storedToken = DB::table('persistent_login_tokens')->where('selector', 'persistent-login-selector')->first();
        $restoredToken = $repository->findBySelector(new PersistentLoginSelector('persistent-login-selector'));

        self::assertNotNull($storedToken);
        self::assertSame($validatorHash, $storedToken->validator_hash);
        self::assertNotSame($plainTextValidator, $storedToken->validator_hash);
        self::assertTrue(Hash::check($plainTextValidator, $storedToken->validator_hash));
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $restoredToken?->accountIdentifier()->value());
        self::assertSame($validatorHash, $restoredToken?->validatorHash()->value());
        self::assertSame('2099-01-31 00:00:00', $restoredToken?->expiresAt()->value()->format('Y-m-d H:i:s'));
        self::assertFalse($restoredToken?->isExpired(new DateTimeImmutable('2099-01-30 23:59:59')) ?? true);
        self::assertTrue($restoredToken?->isExpired(new DateTimeImmutable('2099-01-31 00:00:00')) ?? false);
    }

    public function test_deletes_a_token_by_its_selector(): void
    {
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $this->insertAccount($accountIdentifier);
        $token = new PersistentLoginToken(
            new PersistentLoginSelector('persistent-login-selector'),
            $accountIdentifier,
            new PersistentLoginValidatorHash('$2y$12$opaque-hash'),
            new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00')),
        );
        $repository = $this->app->make(PersistentLoginTokenRepositoryInterface::class);
        $repository->save($token);

        self::assertTrue($repository->deleteBySelector(new PersistentLoginSelector('persistent-login-selector')));
        self::assertFalse($repository->deleteBySelector(new PersistentLoginSelector('persistent-login-selector')));

        self::assertNull($repository->findBySelector(new PersistentLoginSelector('persistent-login-selector')));
        $this->assertDatabaseMissing('persistent_login_tokens', ['selector' => 'persistent-login-selector']);
    }

    private function insertAccount(AccountIdentifier $accountIdentifier): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $accountIdentifier->value(),
            'account_name' => 'Persistent Login User',
            'email_address' => 'persistent-login@example.com',
            'available' => true,
            'status' => 'active',
            'visibility' => 'public',
            'ui_mode' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
