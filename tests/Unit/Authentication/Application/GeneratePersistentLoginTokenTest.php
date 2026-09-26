<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginToken;
use Src\Authentication\Application\UseCase\GeneratePersistentLoginToken\GeneratePersistentLoginTokenInput;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Factory\GeneratedPersistentLoginToken;
use Src\Authentication\Domain\Factory\PersistentLoginTokenFactoryInterface;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class GeneratePersistentLoginTokenTest extends TestCase
{
    public function test_creates_saves_and_returns_a_generated_persistent_login_token(): void
    {
        $accountIdentifier = new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87');
        $token = new PersistentLoginToken(
            new PersistentLoginSelector('selector'),
            $accountIdentifier,
            new PersistentLoginValidatorHash('$2y$12$opaque-hash'),
            new PersistentLoginExpiresAt(new DateTimeImmutable('2099-01-31 00:00:00')),
        );
        $factory = new RecordingPersistentLoginTokenFactory(new GeneratedPersistentLoginToken($token, 'raw-validator'));
        $repository = new RecordingPersistentLoginTokenRepository;

        $output = (new GeneratePersistentLoginToken($factory, $repository))->execute(
            new GeneratePersistentLoginTokenInput($accountIdentifier),
        );

        self::assertSame(1, $factory->calls);
        self::assertSame($accountIdentifier, $factory->accountIdentifier);
        self::assertInstanceOf(DateTimeImmutable::class, $factory->issuedAt);
        self::assertSame(1, $repository->saveCalls);
        self::assertSame($token, $repository->savedToken);
        self::assertSame('selector', $output->selector());
        self::assertSame('raw-validator', $output->rawValidator());
        self::assertSame('2099-01-31 00:00:00', $output->expiresAt()->format('Y-m-d H:i:s'));
    }
}

final class RecordingPersistentLoginTokenFactory implements PersistentLoginTokenFactoryInterface
{
    public int $calls = 0;

    public ?AccountIdentifier $accountIdentifier = null;

    public ?DateTimeImmutable $issuedAt = null;

    public function __construct(private GeneratedPersistentLoginToken $generatedToken) {}

    public function create(AccountIdentifier $accountIdentifier, DateTimeImmutable $issuedAt): GeneratedPersistentLoginToken
    {
        $this->calls++;
        $this->accountIdentifier = $accountIdentifier;
        $this->issuedAt = $issuedAt;

        return $this->generatedToken;
    }
}

final class RecordingPersistentLoginTokenRepository implements PersistentLoginTokenRepositoryInterface
{
    public int $saveCalls = 0;

    public ?PersistentLoginToken $savedToken = null;

    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        return null;
    }

    public function save(PersistentLoginToken $token): void
    {
        $this->saveCalls++;
        $this->savedToken = $token;
    }

    public function deleteBySelector(PersistentLoginSelector $selector): void {}
}
