<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateAccount;

use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;

interface CreateAccountInputPort
{
    public function accountName(): AccountName;

    public function accountBio(): ?AccountBio;

    public function emailAddress(): EmailAddress;

    /** @return list<SocialLink> */
    public function socialLinks(): array;

    public function favoriteTagIdentifiers(): FavoriteTagIdentifiers;

    public function icon(): ?AccountIcon;

    public function header(): ?AccountHeader;
}
