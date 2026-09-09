<?php

declare(strict_types=1);

namespace Src\Account\Domain\Entity;

use DateTimeImmutable;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class Account
{
    private function __construct(
        private readonly AccountIdentifier $accountIdentifier,
        private AccountName $accountName,
        private ?AccountBio $accountBio,
        private readonly EmailAddress $emailAddress,
        /** @var list<SocialLink> */
        private array $socialLinks,
        private FavoriteTagIdentifiers $favoriteTagIdentifiers,
        private AccountStatus $status,
        private ?DateTimeImmutable $banUntil,
        private AccountVisibility $visibility,
        private AccountUiMode $uiMode,
    ) {}

    /** @param list<SocialLink> $socialLinks */
    public static function create(
        AccountIdentifier $accountIdentifier,
        AccountName $accountName,
        ?AccountBio $accountBio,
        EmailAddress $emailAddress,
        array $socialLinks,
        FavoriteTagIdentifiers $favoriteTagIdentifiers,
        AccountVisibility $visibility = AccountVisibility::PUBLIC,
        AccountUiMode $uiMode = AccountUiMode::SYSTEM,
    ): self {
        if (! array_is_list($socialLinks)) {
            throw new \InvalidArgumentException('SNSリンクは一覧で指定する必要があります。');
        }

        foreach ($socialLinks as $socialLink) {
            if (! $socialLink instanceof SocialLink) {
                throw new \InvalidArgumentException('SNSリンクにはSocialLinkのみ指定できます。');
            }
        }

        return new self(
            $accountIdentifier,
            $accountName,
            $accountBio,
            $emailAddress,
            $socialLinks,
            $favoriteTagIdentifiers,
            AccountStatus::ACTIVE,
            null,
            $visibility,
            $uiMode,
        );
    }

    /** @param list<SocialLink> $socialLinks */
    public static function restore(
        AccountIdentifier $accountIdentifier,
        AccountName $accountName,
        ?AccountBio $accountBio,
        EmailAddress $emailAddress,
        array $socialLinks,
        FavoriteTagIdentifiers $favoriteTagIdentifiers,
        AccountStatus $status,
        ?DateTimeImmutable $banUntil,
        AccountVisibility $visibility = AccountVisibility::PUBLIC,
        AccountUiMode $uiMode = AccountUiMode::SYSTEM,
    ): self {
        return new self($accountIdentifier, $accountName, $accountBio, $emailAddress, $socialLinks, $favoriteTagIdentifiers, $status, $banUntil, $visibility, $uiMode);
    }

    public function active(): void
    {
        $this->status = AccountStatus::ACTIVE;
        $this->banUntil = null;
    }

    public function temporarilyBan(): void
    {
        $this->status = AccountStatus::TEMPORARILY_BANNED;
        $this->banUntil = new DateTimeImmutable('+2 weeks');
    }

    public function permanentlyBan(): void
    {
        $this->status = AccountStatus::PERMANENTLY_BANNED;
        $this->banUntil = null;
    }

    public function changeVisibility(AccountVisibility $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function changeUiMode(AccountUiMode $uiMode): void
    {
        $this->uiMode = $uiMode;
    }

    /** @param list<SocialLink>|null $socialLinks */
    public function updateProfile(?AccountName $accountName, bool $hasAccountBio, ?AccountBio $accountBio, ?array $socialLinks, ?FavoriteTagIdentifiers $favoriteTagIdentifiers): void
    {
        if ($accountName !== null) {
            $this->accountName = $accountName;
        }
        if ($hasAccountBio) {
            $this->accountBio = $accountBio;
        }
        if ($socialLinks !== null) {
            if (! array_is_list($socialLinks)) {
                throw new \InvalidArgumentException('SNSリンクは一覧で指定する必要があります。');
            }
            foreach ($socialLinks as $socialLink) {
                if (! $socialLink instanceof SocialLink) {
                    throw new \InvalidArgumentException('SNSリンクにはSocialLinkのみ指定できます。');
                }
            }
            $this->socialLinks = $socialLinks;
        }
        if ($favoriteTagIdentifiers !== null) {
            $this->favoriteTagIdentifiers = $favoriteTagIdentifiers;
        }
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

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

    /** @return list<SocialLink> */
    public function socialLinks(): array
    {
        return $this->socialLinks;
    }

    public function favoriteTagIdentifiers(): FavoriteTagIdentifiers
    {
        return $this->favoriteTagIdentifiers;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    public function banUntil(): ?DateTimeImmutable
    {
        return $this->banUntil;
    }

    public function visibility(): AccountVisibility
    {
        return $this->visibility;
    }

    public function uiMode(): AccountUiMode
    {
        return $this->uiMode;
    }
}
