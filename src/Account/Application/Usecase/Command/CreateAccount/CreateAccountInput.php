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

final readonly class CreateAccountInput implements CreateAccountInputPort
{
    /** @param list<SocialLink> $socialLinks */
    public function __construct(
        private AccountName $accountName,
        private ?AccountBio $accountBio,
        private EmailAddress $emailAddress,
        private array $socialLinks,
        private FavoriteTagIdentifiers $favoriteTagIdentifiers,
        private ?AccountIcon $icon = null,
        private ?AccountHeader $header = null,
    ) {}

    public function accountName(): AccountName
    {
        return $this->accountName;
    }

    public function accountBio(): ?AccountBio
    {
        return $this->accountBio;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function socialLinks(): array
    {
        return $this->socialLinks;
    }

    public function favoriteTagIdentifiers(): FavoriteTagIdentifiers
    {
        return $this->favoriteTagIdentifiers;
    }

    public function icon(): ?AccountIcon
    {
        return $this->icon;
    }

    public function header(): ?AccountHeader
    {
        return $this->header;
    }
}
