<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final class PersistentLoginValueObjectsTest extends TestCase
{
    public function test_selector_retains_a_value_and_uses_the_shared_string_value_object_base(): void
    {
        $selector = new PersistentLoginSelector('selector-value');

        self::assertSame('selector-value', $selector->value());
        self::assertInstanceOf(StringValueObject::class, $selector);
    }

    public function test_selector_rejects_an_empty_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PersistentLoginSelector('');
    }

    public function test_validator_hash_retains_a_value_and_uses_the_shared_string_value_object_base(): void
    {
        $validatorHash = new PersistentLoginValidatorHash('validator-hash');

        self::assertSame('validator-hash', $validatorHash->value());
        self::assertInstanceOf(StringValueObject::class, $validatorHash);
    }

    public function test_validator_hash_rejects_an_empty_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PersistentLoginValidatorHash('');
    }

    public function test_expires_at_retains_the_datetime_value(): void
    {
        $expiresAt = new DateTimeImmutable('2099-01-15 12:34:56');

        self::assertSame($expiresAt, (new PersistentLoginExpiresAt($expiresAt))->value());
    }

    public function test_expires_at_is_fixed_to_thirty_days_after_issuance(): void
    {
        $issuedAt = new DateTimeImmutable('2099-01-01 00:00:00');

        $expiresAt = PersistentLoginExpiresAt::create($issuedAt);

        self::assertSame(30, PersistentLoginExpiresAt::EXPIRATION_DAYS);
        self::assertSame('2099-01-31 00:00:00', $expiresAt->value()->format('Y-m-d H:i:s'));
    }
}
