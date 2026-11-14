<?php

declare(strict_types=1);

namespace Src\ListeningContent\Infrastructure\Query\GetAccountListeningContents;

use Illuminate\Support\Facades\DB;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInputPort;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsInterface;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsOutput;
use Src\ListeningContent\Application\UseCase\Query\GetAccountListeningContents\GetAccountListeningContentsOutputPort;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final class GetAccountListeningContents implements GetAccountListeningContentsInterface
{
    public function execute(GetAccountListeningContentsInputPort $input): ?GetAccountListeningContentsOutputPort
    {
        $accountIdentifier = $input->accountIdentifier();
        $viewerAccountIdentifier = $input->viewerAccountIdentifier();
        $account = DB::table('accounts')->where('account_identifier', $accountIdentifier)
            ->where('available', true)->where('status', 'active');
        if (! $account->exists()) {
            return null;
        }

        if ($viewerAccountIdentifier !== null && $viewerAccountIdentifier !== $accountIdentifier && BlockVisibility::exists($viewerAccountIdentifier, $accountIdentifier)) {
            throw new BlockedAccountVisibilityException;
        }

        PrivateAccountVisibility::exclude($account, $viewerAccountIdentifier, 'accounts.account_identifier', 'accounts.visibility');
        if (! $account->exists()) {
            return new GetAccountListeningContentsOutput([]);
        }

        $contents = DB::table('listening_contents')
            ->join('accounts', 'accounts.account_identifier', '=', 'listening_contents.account_identifier')
            ->where('accounts.account_identifier', $accountIdentifier)
            ->where('accounts.available', true)
            ->where('accounts.status', 'active');
        PrivateAccountVisibility::exclude($contents, $viewerAccountIdentifier, 'accounts.account_identifier', 'accounts.visibility');
        if ($viewerAccountIdentifier !== null && $viewerAccountIdentifier !== $accountIdentifier) {
            BlockVisibility::exclude($contents, $viewerAccountIdentifier, 'accounts.account_identifier');
        }

        $items = $contents
            ->orderBy('listening_contents.created_at')
            ->orderBy('listening_contents.listening_content_identifier')
            ->get(['listening_contents.listening_content_identifier', 'listening_contents.url'])
            ->map(static fn (object $content): array => [
                'listening_content_identifier' => $content->listening_content_identifier,
                'url' => $content->url,
            ])->all();

        return new GetAccountListeningContentsOutput($items);
    }
}
