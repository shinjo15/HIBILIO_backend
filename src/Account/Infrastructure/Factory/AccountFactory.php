<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Factory;

use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Factory\AccountFactoryInterface;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class AccountFactory implements AccountFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountName $accountName, ?AccountBio $accountBio, EmailAddress $emailAddress, array $socialLinks, FavoriteTagIdentifiers $favoriteTagIdentifiers): Account
    {
        return Account::create(new AccountIdentifier($this->uuidService->generate()), $accountName, $accountBio, $emailAddress, $socialLinks, $favoriteTagIdentifiers, AccountVisibility::PUBLIC);
    }
}
