<?php

declare(strict_types=1);

namespace Src\Account\Application\UseCase\UpdateAccountProfile;

use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class UpdateAccountProfileInput implements UpdateAccountProfileInputPort
{
    /** @param list<SocialLink>|null $socialLinks */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private ?AccountName $accountName,
        private bool $hasAccountBio,
        private ?AccountBio $accountBio,
        private ?array $socialLinks,
        private ?FavoriteTagIdentifiers $favoriteTagIdentifiers,
        private ?AccountIcon $icon,
        private bool $deleteIcon,
        private ?AccountHeader $header,
        private bool $deleteHeader,
    ) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountName(): ?AccountName
    {
        return $this->accountName;
    }

    public function hasAccountBio(): bool
    {
        return $this->hasAccountBio;
    }

    public function accountBio(): ?AccountBio
    {
        return $this->accountBio;
    }

    public function socialLinks(): ?array
    {
        return $this->socialLinks;
    }

    public function favoriteTagIdentifiers(): ?FavoriteTagIdentifiers
    {
        return $this->favoriteTagIdentifiers;
    }

    public function icon(): ?AccountIcon
    {
        return $this->icon;
    }

    public function deleteIcon(): bool
    {
        return $this->deleteIcon;
    }

    public function header(): ?AccountHeader
    {
        return $this->header;
    }

    public function deleteHeader(): bool
    {
        return $this->deleteHeader;
    }
}
