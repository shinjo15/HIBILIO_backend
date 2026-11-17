<?php

declare(strict_types=1);

namespace Src\Account\Domain\Factory;

use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Shared\Domain\ValueObject\EmailAddress;

interface AccountFactoryInterface
{
    /** @param list<SocialLink> $socialLinks */
    public function create(
        AccountName $accountName,
        ?AccountBio $accountBio,
        EmailAddress $emailAddress,
        array $socialLinks,
        FavoriteTagIdentifiers $favoriteTagIdentifiers,
    ): Account;
}
