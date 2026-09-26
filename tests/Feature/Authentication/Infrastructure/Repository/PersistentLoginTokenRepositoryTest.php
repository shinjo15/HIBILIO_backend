<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Authentication\Infrastructure\Repository\PersistentLoginTokenRepository;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class PersistentLoginTokenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_restores_and_deletes_a_persistent_login_token_through_the_bound_repository(): void
    {
        $this->insertAccount();
        $repository = $this->app->make(PersistentLoginTokenRepositoryInterface::class);
        $token = new PersistentLoginToken(
            new PersistentLoginSelector('selector'),
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new PersistentLoginValidatorHash('$2y$12$opaque-hash'),
            new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00')),
        );

        $repository->save($token);

        self::assertInstanceOf(PersistentLoginTokenRepository::class, $repository);
        $this->assertDatabaseHas('persistent_login_tokens', [
            'selector' => 'selector',
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'validator_hash' => '$2y$12$opaque-hash',
            'expires_at' => '2099-01-31 00:00:00',
        ]);

        $restored = $repository->find(new PersistentLoginSelector('selector'));

        self::assertSame('selector', $restored?->selector()->value());
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $restored?->accountIdentifier()->value());
        self::assertSame('$2y$12$opaque-hash', $restored?->validatorHash()->value());
        self::assertSame('2099-01-31 00:00:00', $restored?->expiresAt()->value()->format('Y-m-d H:i:s'));

        $repository->delete(new PersistentLoginSelector('selector'));

        self::assertNull($repository->find(new PersistentLoginSelector('selector')));
    }

    private function insertAccount(): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => '3b5581e9-16df-4879-b7d2-5d88dca6ab87',
            'account_name' => '永続ログインユーザー',
            'email_address' => 'persistent-login@example.com',
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
