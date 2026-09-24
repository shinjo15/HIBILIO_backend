<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetAccountDetails;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInputPort;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsOutput;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetAccountDetails implements GetAccountDetailsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetAccountDetailsInputPort $input): GetAccountDetailsOutputPort
    {
        $query = DB::table('accounts')
            ->where('account_identifier', $input->accountIdentifier())
            ->where('available', true)
            ->where('status', 'active');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'accounts.account_identifier');
        }

        $query->select(['account_identifier', 'account_name', 'account_bio', 'visibility', 'ui_mode']);

        if ($input->viewerAccountIdentifier() === null) {
            $query
                ->selectRaw('0 as has_pending_follow_request')
                ->selectRaw('0 as is_following');
        } else {
            $query
                ->selectSub($this->hasPendingFollowRequest($input->viewerAccountIdentifier()), 'has_pending_follow_request')
                ->selectSub($this->isFollowing($input->viewerAccountIdentifier()), 'is_following');
        }

        $account = $query->first();

        if ($account === null) {
            return new GetAccountDetailsOutput(null);
        }

        if ($account->visibility === 'private' && $input->viewerAccountIdentifier() === null) {
            return new GetAccountDetailsOutput(null);
        }

        $isOwnAccount = $input->viewerAccountIdentifier() === $account->account_identifier;
        $isFollowing = ! $isOwnAccount && (bool) $account->is_following;

        if ($account->visibility === 'private' && ! $isFollowing && ! $isOwnAccount) {
            return new GetAccountDetailsOutput([
                'accountIdentifier' => (string) $account->account_identifier,
                'name' => (string) $account->account_name,
                'isDetailed' => false,
            ]);
        }

        $favoriteTags = DB::table('favorite_tags')
            ->join('tags', 'favorite_tags.tag_identifier', '=', 'tags.tag_identifier')
            ->where('favorite_tags.account_identifier', $input->accountIdentifier())
            ->orderBy('tags.tag_name')
            ->orderBy('tags.tag_identifier')
            ->get(['tags.tag_identifier', 'tags.tag_name'])
            ->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
            ])
            ->all();

        $socialLinks = DB::table('account_social_links')
            ->where('account_identifier', $input->accountIdentifier())
            ->orderBy('position')
            ->get(['type', 'url'])
            ->map(static fn (object $socialLink): array => [
                'socialType' => (string) $socialLink->type,
                'socialUrl' => (string) $socialLink->url,
            ])
            ->all();

        return new GetAccountDetailsOutput([
            'accountIdentifier' => (string) $account->account_identifier,
            'name' => (string) $account->account_name,
            'isDetailed' => true,
            'isFollowing' => $isFollowing,
            'bio' => $account->account_bio === null ? null : (string) $account->account_bio,
            'visibility' => (string) $account->visibility,
            'hasPendingFollowRequest' => (bool) $account->has_pending_follow_request,
            'uiMode' => (string) $account->ui_mode,
            'favoriteTags' => $favoriteTags,
            'socialLinks' => $socialLinks,
            'iconImageUrl' => $this->accountImageUrlService->iconImageUrl(new AccountIdentifier((string) $account->account_identifier)),
            'headerImageUrl' => $this->accountImageUrlService->headerImageUrl(new AccountIdentifier((string) $account->account_identifier)),
        ]);
    }

    private function hasPendingFollowRequest(string $viewerAccountIdentifier): mixed
    {
        return DB::table('follow_requests')
            ->selectRaw('count(*) > 0')
            ->where('requesting_account_identifier', $viewerAccountIdentifier)
            ->whereColumn('target_account_identifier', 'accounts.account_identifier')
            ->where('status', 'pending');
    }

    private function isFollowing(string $viewerAccountIdentifier): mixed
    {
        return DB::table('follows')
            ->selectRaw('count(*) > 0')
            ->where('following_account_identifier', $viewerAccountIdentifier)
            ->whereColumn('followed_account_identifier', 'accounts.account_identifier');
    }
}
