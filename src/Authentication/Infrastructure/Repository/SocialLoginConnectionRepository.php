<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use Src\Authentication\Domain\Entity\SocialLoginConnection;
use Src\Authentication\Domain\Repository\SocialLoginConnectionRepositoryInterface;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class SocialLoginConnectionRepository implements SocialLoginConnectionRepositoryInterface
{
    public function findAccountIdentifier(SocialLoginProvider $provider, string $providerUserIdentifier): ?AccountIdentifier
    {
        $identifier = DB::table('social_login_connections')
            ->where('provider', $provider->value)
            ->where('provider_user_identifier', $providerUserIdentifier)
            ->value('account_identifier');

        return is_string($identifier) ? new AccountIdentifier($identifier) : null;
    }

    public function save(SocialLoginConnection $connection): bool
    {
        return DB::table('social_login_connections')->insertOrIgnore([
            'account_identifier' => $connection->accountIdentifier()->value(),
            'provider' => $connection->provider()->value,
            'provider_user_identifier' => $connection->providerUserIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}
