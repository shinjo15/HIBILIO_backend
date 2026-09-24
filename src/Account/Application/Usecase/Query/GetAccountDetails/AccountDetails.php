<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

final readonly class AccountDetails
{
    /**
     * @param  list<array{tagIdentifier: string, tagName: string}>  $favoriteTags
     * @param  list<array{socialType: string, socialUrl: string}>  $socialLinks
     * @param  bool|null  $isFollowing  null for anonymous viewers; otherwise viewer-to-account follow state.
     */
    public function __construct(
        public string $accountIdentifier,
        public string $name,
        public ?string $bio,
        public string $visibility,
        public bool $hasPendingFollowRequest,
        public string $uiMode,
        public array $favoriteTags,
        public array $socialLinks,
        public ?string $iconImageUrl,
        public ?string $headerImageUrl,
        public ?bool $isFollowing,
    ) {}
}
