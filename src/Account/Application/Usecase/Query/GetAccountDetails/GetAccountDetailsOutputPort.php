<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\GetAccountDetails;

/**
 * @phpstan-type AccountDetails array{
 *     accountIdentifier: string,
 *     name: string,
 *     isDetailed: false
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
 *     isFollowing: ?bool
 * }
 */
interface GetAccountDetailsOutputPort
{
    /**
     * null means unavailable. isDetailed=false returns identity only.
     * isFollowing is null for anonymous viewers, otherwise the viewer-to-account follow state.
     *
     * @return AccountDetails|null
     */
    public function accountDetails(): ?array;
}
