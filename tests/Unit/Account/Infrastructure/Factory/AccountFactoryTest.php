<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Infrastructure\Factory\AccountFactory;
use Src\Shared\Application\Service\UuidServiceInterface;

final class AccountFactoryTest extends TestCase
{
    public function test_creates_an_account_with_an_identifier_from_the_uuid_service(): void
    {
        $account = (new AccountFactory(new FixedUuidService))->create(new AccountName('朝活ユーザー'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));

        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $account->accountIdentifier()->value());
        self::assertSame(AccountVisibility::PUBLIC, $account->visibility());
        self::assertSame(AccountUiMode::SYSTEM, $account->uiMode());
    }
}

final class FixedUuidService implements UuidServiceInterface
{
    public function generate(): string
    {
        return '3b5581e9-16df-4879-b7d2-5d88dca6ab87';
    }
}
