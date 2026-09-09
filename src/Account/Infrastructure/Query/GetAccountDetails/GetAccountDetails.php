<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Query\GetAccountDetails;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInputPort;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsOutput;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsOutputPort;

final class GetAccountDetails implements GetAccountDetailsInterface
{
    public function execute(GetAccountDetailsInputPort $input): GetAccountDetailsOutputPort
    {
        $account = DB::table('accounts')
            ->where('account_identifier', $input->accountIdentifier())
            ->where('available', true)
            ->where('status', 'active')
            ->first(['account_identifier', 'account_name', 'account_bio']);

        if ($account === null) {
            return new GetAccountDetailsOutput(null);
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
            'bio' => $account->account_bio === null ? null : (string) $account->account_bio,
            'favoriteTags' => $favoriteTags,
            'socialLinks' => $socialLinks,
        ]);
    }
}
