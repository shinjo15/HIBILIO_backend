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

interface UpdateAccountProfileInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function accountName(): ?AccountName;

    public function hasAccountBio(): bool;

    public function accountBio(): ?AccountBio;

    /** @return list<SocialLink>|null */
    public function socialLinks(): ?array;

    public function favoriteTagIdentifiers(): ?FavoriteTagIdentifiers;

    public function icon(): ?AccountIcon;

    public function deleteIcon(): bool;

    public function header(): ?AccountHeader;

    public function deleteHeader(): bool;
}
