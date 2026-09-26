<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application;

use PHPUnit\Framework\TestCase;
use Src\Authentication\Application\UseCase\RevokePersistentLoginToken\RevokePersistentLoginToken;
use Src\Authentication\Application\UseCase\RevokePersistentLoginToken\RevokePersistentLoginTokenInput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Application\Service\HashServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RevokePersistentLoginTokenTest extends TestCase
{
    public function test_deletes_only_a_token_with_a_matching_validator(): void
    {
        $repository = new RevokeTokenRepository($this->token());
        $output = (new RevokePersistentLoginToken($repository, new RevokeHashService(true)))->execute(new RevokePersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw'));

        self::assertTrue($output->revoked());
        self::assertSame(1, $repository->deleteCount);
    }

    public function test_does_not_delete_a_missing_or_mismatched_token(): void
    {
        $missing = new RevokeTokenRepository(null);
        $mismatched = new RevokeTokenRepository($this->token());

        self::assertFalse((new RevokePersistentLoginToken($missing, new RevokeHashService(true)))->execute(new RevokePersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw'))->revoked());
        self::assertFalse((new RevokePersistentLoginToken($mismatched, new RevokeHashService(false)))->execute(new RevokePersistentLoginTokenInput(new PersistentLoginSelector('selector'), 'raw'))->revoked());
        self::assertSame(0, $missing->deleteCount);
        self::assertSame(0, $mismatched->deleteCount);
    }

    private function token(): PersistentLoginToken
    {
        return new PersistentLoginToken(new PersistentLoginSelector('selector'), new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'), new PersistentLoginValidatorHash('hash'), new PersistentLoginExpiresAt(new \DateTimeImmutable('+1 day')));
    }
}

final class RevokeTokenRepository implements PersistentLoginTokenRepositoryInterface
{
    public int $deleteCount = 0;

    public function __construct(private ?PersistentLoginToken $token) {}

    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        return $this->token;
    }

    public function save(PersistentLoginToken $token): void {}

    public function deleteBySelector(PersistentLoginSelector $selector): bool
    {
        $this->deleteCount++;

        return true;
    }
}

final class RevokeHashService implements HashServiceInterface
{
    public function __construct(private bool $matches) {}

    public function hash(string $value): string
    {
        return 'hash';
    }

    public function matches(string $value, string $hash): bool
    {
        return $this->matches;
    }
}
