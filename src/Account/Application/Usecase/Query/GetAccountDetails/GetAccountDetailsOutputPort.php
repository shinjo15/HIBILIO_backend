<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

/**
 * @phpstan-type AccountDetails array{
 *     accountIdentifier: string,
 *     name: string,
 *     isDetailed: false,
 *     bio: ?string,
 *     visibility: string,
 *     hasPendingFollowRequest: bool,
 *     iconImageUrl: ?string,
 *     headerImageUrl: ?string
 * }|array{
 *     accountIdentifier: string,
 *     name: string,
 *     isDetailed: true,
 *     bio: ?string,
 *     visibility: string,
 *     hasPendingFollowRequest: bool,
 *     uiMode: string,
 *     favoriteTags: list<array{tagIdentifier: string, tagName: string}>,
 *     socialLinks: list<array{socialType: string, socialUrl: string}>,
 *     iconImageUrl: ?string,
 *     headerImageUrl: ?string,
 *     isFollowing: ?bool,
 *     experience?: int,
 *     level?: int,
 *     likeNotificationsEnabled?: bool,
 *     supportNotificationsEnabled?: bool
 * }
 */
interface GetAccountDetailsOutputPort
{
    /**
     * null means unavailable. isDetailed=false returns the minimum public profile.
     * isFollowing is null for anonymous viewers, otherwise the viewer-to-account follow state.
     * Both notification settings are included for the account owner and omitted for all other viewers.
     *
     * @return AccountDetails|null
     */
    public function accountDetails(): ?array;
}
